<?php namespace EscapeWork\Assets\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem as File;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Carbon\Carbon;

class AssetDistCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'asset:dist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a dist folder for your assets to avoid cache';

    /**
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $file;

    /**
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var array
     */
    protected $paths;

    /**
     * Config file location
     */
    protected $configFileLocation = '/config/packages/escapework/laravel-asset-versioning/config.php';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Config $config, File $file, Cache $cache, $paths)
    {
        parent::__construct();

        $this->config = $config;
        $this->file   = $file;
        $this->cache  = $cache;
        $this->paths  = $paths;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $types   = $this->config->get('assets.types');
        $version = Carbon::now()->timestamp;

        $this->updateConfigVersion($version);
        $this->deleteOldDirectories($types);
        $this->createDistDirectories($types, $version);
    }

    public function updateConfigVersion($version)
    {
        $this->cache->forever('laravel-asset-versioning.version', $version);
    }

    public function deleteOldDirectories($types)
    {
        foreach ($types as $type => $directories) {
            $dir = $this->paths['public'] . '/' . $directories['dist_dir'];

            $this->file->cleanDirectory($dir);
        }
    }

    public function createDistDirectories($types, $version)
    {
        foreach ($types as $type => $directories) {
            $origin_dir = $this->paths['public'].'/'.$directories['origin_dir'];
            $dist_dir   = $this->paths['public'].'/'.$directories['dist_dir'].'/'.$version;

            $this->file->copyDirectory($origin_dir, $dist_dir);

            $this->info($type . ' dist dir ('.$dist_dir.') successfully created!');
        }
    }
}
