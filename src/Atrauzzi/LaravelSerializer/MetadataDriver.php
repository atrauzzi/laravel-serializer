<?php namespace Atrauzzi\LaravelSerializer {

	use JMS\Serializer\Metadata\PropertyMetadata;
	use Metadata\Driver\AdvancedDriverInterface;
	//
	use Illuminate\Config\Repository;
	//
	use JMS\Serializer\Metadata\ClassMetadata;
	use ReflectionClass;


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
			return array_keys($this->config('serializer::mappings'));
		}

		/**
		 * @param \ReflectionClass $class
		 *
		 * @return \Metadata\ClassMetadata
		 */
		public function loadMetadataForClass(ReflectionClass $class) {

			$className = $class->name;
			$mappingConfig = $this->config(sprintf('serializer::mappings.%s', $className));

			$classMetadata = new ClassMetadata($className);

			// Only serialize attributes from the L4 config array.
			foreach($mappingConfig as $attribute => $config) {

				// Check for a mutator method.
					// If present, use it.
				// Else check for attribute.
					// If present, use it.
				// Else, skip the attribute.

				$propertyMetadata = new PropertyMetadata();

			}

		}

	}

}