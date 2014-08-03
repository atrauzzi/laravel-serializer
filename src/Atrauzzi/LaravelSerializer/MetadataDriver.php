<?php namespace Atrauzzi\LaravelSerializer {

	use JMS\Serializer\Metadata\PropertyMetadata;
	use JMS\Serializer\Metadata\VirtualPropertyMetadata;
	use Metadata\Driver\AdvancedDriverInterface;
	//
	use Illuminate\Config\Repository;
	//
	use JMS\Serializer\Metadata\ClassMetadata;
	use ReflectionClass;
	use Atrauzzi\LaravelSerializer\Exception\UnsupportedType as UnsupportedTypeException;


	/**
	 * Class MetadataDriver
	 *
	 * This metadata driver integrates JMS Serializer with the Laravel Framework
	 *
	 * Mappings are maintained as Laravel configuration files and are read on demand.  Conventions mimic the
	 * mutator system already present in Eloquent so that the language remains consistent for the majority of cases.
	 *
	 * @package Atrauzzi\LaravelSerializer
	 */
	class MetadataDriver implements AdvancedDriverInterface {

		/** @var \Illuminate\Config\Repository */
		protected $config;

		public function __construct(
			Repository $config
		) {
			$this->config = $config;
		}

		/**
		 * Gets all the metadata class names known to this driver.
		 *
		 * @return array
		 */
		public function getAllClassNames() {
			return array_keys($this->config->get('serializer::mappings'));
		}

		/**
		 * When serializer wants to serialize a class, it will ask this method to produce the metadata.
		 *
		 * @param \ReflectionClass $class
		 * @return \Metadata\ClassMetadata
		 */
		public function loadMetadataForClass(ReflectionClass $class) {

			$className = $class->name;
			$classMetadata = new ClassMetadata($className);
			$mappingConfig = $this->config->get(sprintf('serializer::mappings.%s', $className));

			// If the class is an instance of Model, as a convenience, pre-configure $visible as defaults.
			if($class->isSubclassOf('Illuminate\Database\Eloquent\Model')) {

				$defaultProperties = $class->getDefaultProperties();

				if(!empty($defaultProperties['visible']))
					$mappingConfig['attributes'] = array_merge($defaultProperties['visible'], $mappingConfig['attributes']);

			}

			//
			// Generate a default discriminator map if one isn't provided.

			$discriminatorMap = !empty($mappingConfig['discriminator_map']) ?
				$mappingConfig['discriminator_map']
				: [strtolower($class->getShortName()) => $className]
			;
			$classMetadata->setDiscriminator('_type', $discriminatorMap);

			//
			//

			if(!empty($mappingConfig['attributes'])) {

				// Only serialize attributes present in the L4 config array.
				foreach($mappingConfig['attributes'] as $attribute => $attributeConfig) {

					//
					// Select a property metadata class.

					// If there's a mutator method, it's virtual.
					$mutatorMethod = sprintf('get%sAttribute', studly_case($attribute));
					if($class->hasMethod($mutatorMethod))
						$propertyMetadata = new VirtualPropertyMetadata($class->name, $mutatorMethod);
					// If it's a normal attribute or on an instance of Model.
					elseif($class->hasProperty($attribute) || $class->isSubclassOf('Illuminate\Database\Eloquent\Model'))
						$propertyMetadata = new PropertyMetadata($class->name, $attribute);

					//
					//

					if(!empty($propertyMetadata)) {

						//
						// Additional property config processing.

						// An array config for the attribute means the attribute has more to set up.
						if(is_array($attributeConfig)) {
							foreach($attributeConfig as $config => $value) {
								$metadataSetMethod = sprintf('set%sMetadata', studly_case($config));
								$this->$metadataSetMethod($propertyMetadata, $value);
							}
						}
						// A string config for the attribute means we're just being told to map the type.
						elseif(is_string($attributeConfig)) {
							$this->setTypeMetadata($propertyMetadata, $attributeConfig);
						}
						// else - Any other value/null just lives with the defaults.

						//
						//

						$classMetadata->addPropertyMetadata($propertyMetadata);

					}

				}

			}

			return $classMetadata;

		}

		//
		//
		//

		/**
		 * Sets the name that will field will be known as in the serialization output.
		 *
		 * @param PropertyMetadata $propertyMetadata
		 * @param string $name
		 */
		protected function setNameMetadata(PropertyMetadata $propertyMetadata, $name) {
			$propertyMetadata->serializedName = $name;
		}

		/**
		 * Assigns the data type for a property.
		 *
		 * @param PropertyMetadata $propertyMetadata
		 * @param string $type
		 * @throws Exception\UnsupportedType
		 */
		protected function setTypeMetadata(PropertyMetadata $propertyMetadata, $type) {

			if(!$this->typeSupported($type))
				throw new UnsupportedTypeException($type);

			$propertyMetadata->setType($type);

		}

		/**
		 * Checks to see if a type string is supportable.
		 *
		 * ToDo: This can easily be expanded to support serializer's more complex type strings
		 * http://jmsyst.com/libs/serializer/master/reference/annotations#type
		 *
		 * @param string $type
		 * @return bool
		 */
		protected function typeSupported($type) {

			if(in_array($type, [
				'integer',
				'boolean',
				'double',
				'string',
				'array',
				'DateTime'
			]))
				return true;

			return false;

		}

	}

}