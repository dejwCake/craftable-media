<?php

declare(strict_types=1);

namespace Brackets\Media\Tests;

use Brackets\Media\MediaServiceProvider;
use Brackets\Media\UrlGenerator\LocalUrlGenerator;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Env;
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

        $this->initializeDirectory($this->getTempDirectory());

        $this->setUpDatabase($this->app);
        $this->setUpTempTestFiles();

        $this->testModel = TestModel::first();
        $this->testModelWithCollections = TestModelWithCollections::first();

        // let's define simple routes
        $this->app->make(Router::class)->post('/test-model/create', static function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            return TestModelWithCollections::create($sanitized);
        });

        $this->app->make(Router::class)->post('/test-model-disabled/create', static function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            return TestModelWithCollectionsDisabledAutoProcess::create($sanitized);
        });
    }

    public function tearDown(): void
    {
        $filesystem = $this->app->make(Filesystem::class);
        if ($filesystem->isDirectory($this->getTempDirectory())) {
            $filesystem->deleteDirectory($this->getTempDirectory());
        }

        parent::tearDown();
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
        if (Env::get('DB_CONNECTION') === 'pgsql') {
            $app->make(Config::class)->set('database.default', 'pgsql');
            $app->make(Config::class)->set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => 'pgsql',
                'port' => '5432',
                'database' => Env::get('DB_DATABASE', 'laravel'),
                'username' => Env::get('DB_USERNAME', 'root'),
                'password' => Env::get('DB_PASSWORD', 'bestsecret'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else if (Env::get('DB_CONNECTION') === 'mysql') {
            $app->make(Config::class)->set('database.default', 'mysql');
            $app->make(Config::class)->set('database.connections.mysql', [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => '3306',
                'database' => Env::get('DB_DATABASE', 'laravel'),
                'username' => Env::get('DB_USERNAME', 'root'),
                'password' => Env::get('DB_PASSWORD', 'bestsecret'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else {
            $app->make(Config::class)->set('database.default', 'sqlite');
            $app->make(Config::class)->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        // FIXME these config setting needs to have a look
        $app->make(Config::class)->set('filesystems.disks.media', [
            'driver' => 'local',
            'root' => $app->make('path.public') . '/media',
            'url' => '/media',
        ]);


        // FIXME these config setting needs to have a look
        $app->make(Config::class)->set('filesystems.disks.media_private', [

            'driver' => 'local',
            'root' => $this->getMediaDirectory('storage'),
        ]);

        $app->make(Config::class)->set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => $this->getUploadsDirectory(),
        ]);

        $app->make(Config::class)->set('media-collections', [
            'public_disk' => 'media',
            'private_disk' => 'media_private',

            'auto_process' => true,
        ]);

        $app->make(Config::class)->set('media-library.url_generator', LocalUrlGenerator::class);

        // FIXME these config setting needs to have a look
        $app->bind('path.public', fn () => $this->getTempDirectory());

        // FIXME these config setting needs to have a look
        $app->bind('path.storage', fn () => $this->getTempDirectory());

        $app->make(Config::class)->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    protected function setUpDatabase(Application $app): void
    {
        $app->make('db')->connection()->getSchemaBuilder()->create(
            'test_models',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
                $table->integer('width')->nullable();
            },
        );

        TestModel::create(['name' => 'test']);

        $app->make('db')->connection()->getSchemaBuilder()->create('media', static function (Blueprint $table): void {
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
        $filesystem = $this->app->make(Filesystem::class);
        $filesystem->copyDirectory(__DIR__ . '/testfiles', $this->getTestFilesDirectory());
        $filesystem->copyDirectory(__DIR__ . '/testfiles', $this->getUploadsDirectory());
    }

    protected function initializeDirectory(string $directory): void
    {
        $filesystem = $this->app->make(Filesystem::class);
        if ($filesystem->isDirectory($directory)) {
            $filesystem->deleteDirectory($directory);
        }
        $filesystem->makeDirectory($directory);
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
        $gate = $this->app->make(Gate::class);
        //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        $gate->define('admin', static fn ($user) => true);
        //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        $gate->define('admin.upload', static fn ($user) => true);
    }
}
