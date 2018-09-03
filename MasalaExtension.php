<?php

namespace Masala;

use Exception,
    Nette\DI\CompilerExtension,
    Nette\PhpGenerator\ClassType;

final class MasalaExtension extends CompilerExtension {

    private $defaults = ['assets' => 'assets/masala',
        'feeds' => 'feeds',
        'format' => ['date' => ['build' => 'd.m.Y', 'query'=> 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")'],
                    'time' => ['build' => 'Y-m-d H:i:s', 'query' => 'Y-m-d', 'select' => 'GET_FORMAT(DATE,"EUR")']],
        'help' => 'help',
        'npm' => 'node_modules',
        'keywords' => 'keywords',
        'log' => 'log',
        'pagination' => 20,
        'settings' => 'settings',
        'speed' => 50,
        'spice' => 'spice',
        'upload' => 10,
        'write' => 'write'];

    public function getConfiguration(array $parameters) {
        foreach($this->defaults as $key => $parameter) {
            if(!isset($parameters['masala'][$key])) {
                $parameters['masala'][$key] = $parameter;
            }
        }
        return $parameters;
    }
    
    public function loadConfiguration() {
        $builder = $this->getContainerBuilder();
        $parameters = $this->getConfiguration($builder->parameters);
        $manifests = json_decode(file_get_contents($parameters['wwwDir'] . '/' . $parameters['masala']['assets'] . '/js/manifest.json'));
        $js = [];
        foreach($manifests as $key => $manifest) {
            $js[$key] = preg_replace('/\.\.\//', '', $manifest);
        }
        $builder->addDefinition($this->prefix('Builder'))
                ->setFactory('Masala\Builder', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('masalaExtension'))
                ->setFactory('Masala\MasalaExtension', []);
        $builder->addDefinition($this->prefix('contentForm'))
                ->setFactory('Masala\ContentForm', [$js['ContentForm.js']]);
        $builder->addDefinition($this->prefix('exportService'))
                ->setFactory('Masala\ExportService', [$builder->parameters['tempDir']]);
        $builder->addDefinition($this->prefix('emptyRow'))
                ->setFactory('Masala\EmptyRow');
        $builder->addDefinition($this->prefix('grid'))
                ->setFactory('Masala\Grid', [$parameters['appDir'], $js['Grid.js'], $parameters['masala']]);
        $builder->addDefinition($this->prefix('filterForm'))
                ->setFactory('Masala\FilterForm', ['']);
        $builder->addDefinition($this->prefix('importForm'))
                ->setFactory('Masala\ImportForm', [$js['ImportForm.js']]);
        $builder->addDefinition($this->prefix('helpRepository'))
                ->setFactory('Masala\HelpRepository', [$parameters['masala']['help']]);
        $builder->addDefinition($this->prefix('keywordsRepository'))
                ->setFactory('Masala\KeywordsRepository', [$parameters['masala']['keywords']]);
        $builder->addDefinition($this->prefix('masala'))
                ->setFactory('Masala\Masala', [$parameters['masala']]);
        $builder->addDefinition($this->prefix('mockRepository'))
                ->setFactory('Masala\MockRepository');
        $builder->addDefinition($this->prefix('mockService'))
                ->setFactory('Masala\MockService');
        $builder->addDefinition($this->prefix('rowForm'))
                ->setFactory('Masala\RowForm', [$js['RowForm.js']]);
        $builder->addDefinition($this->prefix('writeRepository'))
                ->setFactory('Masala\WriteRepository', [$parameters['masala']['write']]);
    }

    public function beforeCompile() {
        if(!class_exists('Nette\Application\Application')) {
            throw new MissingDependencyException('Please install and enable https://github.com/nette/nette.');
        }
        parent::beforeCompile();
    }

    public function afterCompile(ClassType $class) {
    }

}

class MissingDependencyException extends Exception { }
