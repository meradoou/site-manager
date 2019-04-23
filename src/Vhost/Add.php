<?php
namespace Miradoz\SiteManager\Vhost;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Monolog\Logger;

class Add extends Command
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('vhost:add');
        $this->setDescription('créer un virtualhost sous Apache.');
        $this->addArgument('domain', InputArgument::REQUIRED);
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $domain = $input->getArgument('domain');
    
            $template = file_get_contents(SITE_VHOST_TEMPLATE . '/vhost.txt');
    
            $output->writeln("<info>Création du virtualhost {$domain}</info>");
    
            if (!is_writable(SITE_DOCUMENT_ROOT) || !is_writable(SITE_VHOST_CONFIG) || !is_writable(SITE_HOSTS)) {
                throw new \Exception('Veuillez utiliser un utilisateur qui a accès aux dossiers "' . SITE_DOCUMENT_ROOT . '" et  "' . SITE_VHOST_CONFIG . '"');
            }


            $basePath = SITE_DOCUMENT_ROOT . '/' . $domain;
            $filename = sprintf('%s/%s.conf', SITE_VHOST_CONFIG , $domain);

            if (file_exists($basePath) || file_exists($filename)) {
                throw new \Exception("Le domaine existe déjà.");
            }

            $output->writeln("Le dossier racine : {$basePath}");
    
            mkdir($basePath . '/httpdocs', 0777, true);
            mkdir($basePath . '/logs', 0777, true);
    
            
            $output->writeln("Le fichier de configuration : {$filename}");
    
            $template = str_replace('VHOST_DOMAIN', $domain, $template);
            file_put_contents($filename, $template);

            $output->writeln('Redemarrer le serveur Apache');
            system('systemctl restart httpd');

            $output->writeln("Ajout du domaine {$domain} dans <comment>/etc/host</comment>");

            $hosts = file_get_contents(SITE_HOSTS);
            $hosts .= "\n127.0.0.1\t{$domain}\t\t{$domain}\n";
            file_put_contents(SITE_HOSTS, $hosts);

            $output->writeln('<info>Terminé.</info>');
        } catch(\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}