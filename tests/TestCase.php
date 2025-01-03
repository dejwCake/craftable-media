<?php

namespace Brackets\Media\Tests;

use Brackets\Media\MediaServiceProvider;
use Brackets\Media\UrlGenerator\LocalUrlGenerator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Throwable;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @var TestModel */
    protected TestModel $testModel;

    /** @var TestModelWithCollections */
    protected TestModelWithCollections $testModelWithCollections;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        $this->setUpTempTestFiles();

        $this->testModel = TestModel::first();
        $this->testModelWithCollections = TestModelWithCollections::first();

        // let's define simple routes
        $this->app['router']->post('/test-model/create', function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            $testModel = TestModelWithCollections::create($sanitized);

            return $testModel;
        });

        $this->app['router']->post('/test-model-disabled/create', function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            $testModel = TestModelWithCollectionsDisabledAutoProcess::create($sanitized);

            return $testModel;
        });
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            MediaServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $this->initializeDirectory($this->getTempDirectory());

        if (env('DB_CONNECTION') === 'pgsql') {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => 'pgsql',
                'port' => '5432',
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'bestsecret'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else if (env('DB_CONNECTION') === 'mysql') {
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => '3306',
                'database' => env('DB_DATABASE', 'laravel'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'bestsecret'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        // FIXME these config setting needs to have a look
        $app['config']->set('filesystems.disks.media', [
            'driver' => 'local',
            'root' => $this->getMediaDirectory(),
        ]);


        // FIXME these config setting needs to have a look
        $app['config']->set('filesystems.disks.media_private', [

            'driver' => 'local',
            'root' => $this->getMediaDirectory('storage'),
        ]);

        $app['config']->set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => $this->getUploadsDirectory(),
        ]);

        $app['config']->set('media-collections', [
            'public_disk' => 'media',
            'private_disk' => 'media_private',

            'auto_process' => true,
        ]);

        $app['config']->set('media-library.url_generator', LocalUrlGenerator::class);

        // FIXME these config setting needs to have a look
        $app->bind('path.public', function () {
            return $this->getTempDirectory();
        });

        // FIXME these config setting needs to have a look
        $app->bind('path.storage', function () {
            return $this->getTempDirectory();
        });

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    /**
     * @param Application $app
     */
    protected function setUpDatabase(Application $app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('width')->nullable();
        });

        TestModel::create(['name' => 'test']);

        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });
    }

    // FIXME what is this method for?
    protected function setUpTempTestFiles(): void
    {
        $this->initializeDirectory($this->getTestFilesDirectory());
        $this->initializeDirectory($this->getUploadsDirectory());
        File::copyDirectory(__DIR__ . '/testfiles', $this->getTestFilesDirectory());
        File::copyDirectory(__DIR__ . '/testfiles', $this->getUploadsDirectory());
    }

    /**
     * @param $directory
     */
    protected function initializeDirectory($directory): void
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function getTempDirectory(string $suffix = ''): string
    {
        return __DIR__ . '/temp' . ($suffix === '' ? '' : '/' . $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function getMediaDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory('media') . ($suffix === '' ? '' : '/' . $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function getUploadsDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory('uploads') . ($suffix === '' ? '' : '/' . $suffix);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function getTestFilesDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory('app') . ($suffix === '' ? '' : '/' . $suffix);
    }

    /**
     * Disable authorization
     */
    public function disableAuthorization(): void
    {
        $this->actingAs(new User, 'admin');
        Gate::define('admin', static function ($user) {
            return true;
        });
        Gate::define('admin.upload', static function ($user) {
            return true;
        });
    }
}
