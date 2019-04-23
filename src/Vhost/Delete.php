<?php
namespace Miradoz\SiteManager\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Monolog\Logger;

class Delete extends Command
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('vhost:delete');
        $this->setDescription('supprimer un virtualhost sous Apache.');
        $this->addArgument('domain', InputArgument::REQUIRED);
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $domain = $input->getArgument('domain');
    
            $template = file_get_contents(SITE_VHOST_TEMPLATE . '/vhost.txt');
    
            $output->writeln("<info>Suppression du virtualhost {$domain}</info>");
    
            if (!is_writable(SITE_DOCUMENT_ROOT) || !is_writable(SITE_VHOST_CONFIG) || !is_writable(SITE_HOSTS)) {
                throw new \Exception('Veuillez utiliser un utilisateur qui a accès aux dossiers "' . SITE_DOCUMENT_ROOT . '" et  "' . SITE_VHOST_CONFIG . '"');
            }


            $basePath = SITE_DOCUMENT_ROOT . '/' . $domain;
            $filename = sprintf('%s/%s.conf', SITE_VHOST_CONFIG , $domain);

            if (!file_exists($basePath) || !file_exists($filename)) {
                throw new \Exception("Le domaine <comment>{$domain}</comment> est introuvable.");
            }

            $output->writeln('Arreter le serveur Apache');
            system('systemctl stop httpd');

            $this->deleteDirectory($basePath . '/httpdocs');
            $this->deleteDirectory($basePath . '/logs');
            $this->deleteDirectory($basePath);
            
            $hosts = file_get_contents(SITE_HOSTS);
            $row = "127.0.0.1\t{$domain}\t\t{$domain}\n";

            $hosts = str_replace($row, "\n", $hosts);

            file_put_contents(SITE_HOSTS, $hosts);

            $output->writeln('Demarrer le serveur Apache');
            system('systemctl start httpd');

            $output->writeln('<info>Terminé.</info>');
        } catch(\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }

    protected function deleteDirectory($dir) 
    {
        if (!file_exists($dir)) {
            return true;
        }
    
        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
    
        }
    
        return rmdir($dir);
    }    
}