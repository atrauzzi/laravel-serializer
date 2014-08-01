<?php namespace Atrauzzi\LaravelSerializer {

	use Illuminate\Support\ServiceProvider as Base;
	//
	use Illuminate\Foundation\Application;
	use JMS\Serializer\SerializerBuilder;


	class ServiceProvider extends Base {

		public function boot() {
			$this->package('atrauzzi/laravel-serializer', 'serializer');
		}

		public function register() {

			$this->app->bind('JMS\Serializer\Serializer', function (Application $app) {

				/** @var \Illuminate\Config\Repository $config */
				$config = $app->make('Illuminate\Config\Repository');

				return SerializerBuilder
					::create()
					->setCacheDir(storage_path('serializer'))
					->setDebug($config->get('app.debug'))
					->build()
				;

			});

		}

	}

}