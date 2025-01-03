<?php

declare(strict_types=1);

namespace Brackets\Media\Tests;

use Brackets\Media\MediaServiceProvider;
use Brackets\Media\UrlGenerator\LocalUrlGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected TestModel $testModel;

    protected TestModelWithCollections $testModelWithCollections;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        $this->setUpTempTestFiles();

        $this->testModel = TestModel::first();
        $this->testModelWithCollections = TestModelWithCollections::first();

        // let's define simple routes
        $this->app['router']->post('/test-model/create', static function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            return TestModelWithCollections::create($sanitized);
        });

        $this->app['router']->post('/test-model-disabled/create', static function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            return TestModelWithCollectionsDisabledAutoProcess::create($sanitized);
        });
    }

    /**
     * @param Application $app
     * @return array<class-string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
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
        $app->bind('path.public', fn () => $this->getTempDirectory());

        // FIXME these config setting needs to have a look
        $app->bind('path.storage', fn () => $this->getTempDirectory());

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    protected function setUpDatabase(Application $app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->integer('width')->nullable();
        });

        TestModel::create(['name' => 'test']);

        Schema::create('media', static function (Blueprint $table): void {
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

    protected function initializeDirectory(string $directory): void
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    public function getTempDirectory(string $suffix = ''): string
    {
        return __DIR__ . '/temp' . ($suffix === '' ? '' : '/' . $suffix);
    }

    public function getMediaDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory('media') . ($suffix === '' ? '' : '/' . $suffix);
    }

    public function getUploadsDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory('uploads') . ($suffix === '' ? '' : '/' . $suffix);
    }

    public function getTestFilesDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory('app') . ($suffix === '' ? '' : '/' . $suffix);
    }

    /**
     * Disable authorization
     */
    public function disableAuthorization(): void
    {
        $this->actingAs(new User(), 'admin');
        //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        Gate::define('admin', static fn ($user) => true);
        //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        Gate::define('admin.upload', static fn ($user) => true);
    }
}
