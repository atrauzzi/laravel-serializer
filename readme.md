# Laravel Serializer

Binds the JMS serializer library to Laravel and allows schemas to be defined according to normal Laravel configuration conventions.

## Setup

To get Laravel Serializer ready for use in your project, take the usual steps for setting up a Laravel package.

 * Add `atrauzzi/laravel-serializer` to your `composer.json` file.
 * Run `composer update` at the root of your project.
 * Edit your `app/config/app.php` file and add:
   * `'Atrauzzi\LaravelSerializer\ServiceProvider',` somewhere near the bottom of your `providers` array
 * Take a project-level copy of the configuration `./artisan config:publish atrauzzi/laravel-serializer`

```
Note: Because this package does not perform any data storage, no migrations are required.
```

## Configuring

Internally, the JMS serializer library makes use of metadata mappings. Normally these are configured via XML, YAML or
annotations on the class being serialized.  Laravel Serializer on the other hand is opinionated in that those
definitions should be external to the business objects and kept in a format that is native to Laravel projects.  As a
result, the mappings are stored as part of the normal Laravel configuration array system.

If you wish to override this and configure serializer to use one of the default or a custom metadata driver
factory you've made, all you have to do is re-bind `JMS\Serializer\Builder\DriverFactoryInterface` in the dependency
injection container.


### Mappings
If you open the copy of `config.php` that was created by the last step during setup, you will see it is already
populated with a sample serializer mapping.

The mapping configuration schema is fairly simple and follows the form of:

    [
        'mappings' => [
            'My\Namespace\Domain\User' => [
                'attributes' => [
                    'id' => 'integer',
                    'name' => [
                        'type' => 'string'
                    ]
                ]
            ]
        ]
    ]

Every key under `mappings` is the fully qualified class name of the object that will be serialized.  Every key under
`attributes` within each class is the name of an attribute or mutator method (more on mutators in the usage section).
The value specified under each key in `mappings` can be either an `array`, a `string` or unspecified.  When the value
is a string, that string will be the type used for that field.  When the value is an array, multiple configuration
directives can be specified for a single field.  A key with no value will be given a best-guess default
mapping configuration.

## Usage

Because you're looking to serialize data, I'm going to assume that you already have some form of domain objects to
work with in your project.

When performing serialization, each field in the mapping configuration indicates either a property or a mutator method,
similar to the mutators that can be created on Eloquent models.  That said, you can also define methods following the
same convention if you are using any other ORM and/or POPOs.

Serialization is done by requesting an instance of `JMS\Serializer\Serializer` from the Laravel container.  Once you
have that instance, you can make use of it as per the [normal JMS serializer docs](http://jmsyst.com/libs/serializer/master/usage).

Let's assume the following mapping configuration:

    ...
	[
		'attributes' => [
			'firstName'
			'fullName' => [
				'type' => 'string'
			]
		]
	]
	...

And along with it, we'll also assume a business object has the following defined:

    protected $firstName;

    protected $lastName;

    public function getFullNameAttribute() {
        return $this->userName . ' ' . $this->lastName;
    }

Your controller will look something like this:

    /** @var \JMS\Serializer\Serializer */
	protected $serializer;

	/**
	 * @param \JMS\Serializer\Serializer $serializer
	 */
	public function __construct(
		Serializer $serializer
	) {
		$this->serializer = $serializer;
	}

    public function myController() {
        // ...
		$serializedData = $this->serializer->serialize($myInstance, 'json');
        // ...
    }

In this scenario, when serialzing to JSON, the following schema will be generated:

    {
        "_type": "...",
        "first_name": "...",
        "full_name": "... ..."
    }

As you can see, `firstName` was output, and `fullName` was mutated from `firstName` and `lastName`.  But the standalone
`lastName` attribute was not ouput because it was not specified in the mapping configuration.  You may also observe
that a `_type` field was generated as a convenience for any consumers that might be interested in mixed and polymorphic
result sets.



### Separation of Concerns

It might occur to you - especially if you are using Doctrine - that not all mutators belong on the classes that contain
the data they're mutating.  Often times, the mutation might be as a result of the circumstantial combination of two or
more concerns. The best and most common example of this is when generating canonical URIs for objects.  Ideally you
should **not** be writing a `getUri` method on your models as that creates bad implicit dependencies.

In these situations, it makes more sense to leverage composition and create a class that can be made by Laravel's
dependency injection container.
Instances of this class have the instance of your model object assigned to them after having their `__construct` method
called.

    class PostSerializer {

        protected $uriGeneratorService;

        protected $post;

        public function __construct(UriGeneratorService $uriGeneratorService) {
            $this->uriGeneratorService = $uriGeneratorService;
        }

        public function setPost(Post $post) {
            $this->post = $post;
        }

        public function getUriAttribute() {
            return $this->uriGeneratorService->getUriForPost($this->post);
        }

    }

Given the example above, additional properties from the `Post` itself could be output via mutators that pass the data
upwards.  In this sense, `PostSerializer` has become a kind of view that helps keep your domain class clean and portable
by maintaining a sensible separation of concerns.

### Eloquent

Laravel's default ORM is Eloquent and is a very convenient tool for manipulating queries and modeling data.  As a
side effect, it tends to bundle many non-domain concerns inside of model classes.
Rather than ignore that useful metadata, this integration attempts to leverage it to minimize duplication of your
efforts while authoring project code.  Following is a list of some of the shortcuts you can enjoy if you're using
Eloquent for data storage.

 * Mutators are detected and used by Laravel Serializer when they are present.
 * The `$visible` property on subclasses of `Model` is read during metadata setup. The most basic of setups is assumed
 for each field, however this might be sufficient for your needs and if so, means you will not need to create
 configuration mappings outside of your model classes.

## Meta

The documentation and some of the functionality in this library is still evolving.  If there's a feature or improvement
that you would like to contribute or suggest, please don't hesitate to open a github ticket! :)

### Credits

Laravel Serializer is created and maintained by [Alexander Trauzzi](http://goo.gl/Bq49Bg)
