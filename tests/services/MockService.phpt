<?php

namespace Test;

use Masala\EditForm,
    Masala\MockModel,
    Masala\MockService,
    Masala\RowBuilder,
    Models\TranslatorModel,
    Nette\DI\Container,
    Nette\Caching\Storages\FileStorage,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Structure,
    Nette\Http\UrlScript,
    Nette\Http\Request,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class MockServiceTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var MockModel */
    private $mockModel;

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var Request */
    private $request;

    /** @var FileStorage */
    private $cacheStorage;

    /** @var Context */
    private $context;

    /** @var MockService */
    private $class;

    function __construct(Container $container) {
        $this->container = $container;
    }

    function setUp() {
        /** database */
        $connection = new Connection($this->container->parameters['database']['dsn'], $this->container->parameters['database']['user'], $this->container->parameters['database']['password']);
        $this->cacheStorage = new FileStorage(__DIR__ . '/../../../temp');
        $structure = new Structure($connection, $this->cacheStorage);
        $this->context = new Context($connection, $structure, null, $this->cacheStorage);
        $parameters = $this->container->getParameters();
        /** models */
        $this->mockModel = new MockModel($this->context, $this->cacheStorage);
        $this->translatorModel = new TranslatorModel($this->container->parameters['localization'], $this->container->parameters['tables']['translator'], $this->context, $this->cacheStorage);
        $this->class = new MockService($this->container, $this->translatorModel);
        $this->tables = $this->mockModel->getTestTables();
        $urlScript = new UrlScript();
        $this->request = new Request($urlScript);
    }

    function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testGetCall() {
        Assert::true(method_exists($this->class, 'getCall'), 'MockService:getCall method is not set.');
        $compulsories = (isset($this->container->parameters['mockService']['calls'])) ? $this->container->parameters['mockService']['calls'] : [];
        $presenters = [];
        $parameters = $this->container->parameters['masala'];
        foreach ($compulsories as $table => $annotations) {
            Assert::true(!is_array($annotations) or isset($parameters[$table]), 'Compulsory for Masala config annotation ' . $table . ' is not used. Do you wish to remove it?');
        }
        foreach ($parameters as $table => $annotations) {
            Assert::true(!is_array($annotations) or isset($compulsories[$table]['class']), 'Compulsory for Masala config annotation ' . $table . ' was not set. You must add test.');
            if (is_array($annotations) and ! isset($presenters[$compulsories[$table]['class']])) {
                $tableName = preg_replace('/\.(.*)/', '', $table);
                $row = $this->mockModel->getTestRow($tableName);
                $presenters[$compulsories[$table]['class']] = $this->class->getPresenter($compulsories[$table]['class'], WWW_DIR . '/' . $compulsories[$table]['latte'], ['id' => $row->id]);
                Assert::true(is_object($presenter = $presenters[$compulsories[$table]['class']]), 'Presenter ' . $compulsories[$table]['class'] . ' was not instantiated.');
                Assert::false(empty($presenter->getAction()), 'Action of presenter ' . $compulsories[$table]['class'] . ' is not set for annotation ' . $table . '.');
                $setting = new RowBuilder($parameters, $this->context, $this->cacheStorage);
                $grid = $this->class->getBuilder($presenter->getName(), 'default');
                $form = new EditForm(10, $this->translatorModel, $this->class, $this->request);
                $form->setRow($setting->table($tableName));
                $presenters[$compulsories[$table]['class']]->addComponent($form, 'EditForm');
                Assert::true(is_object($presenters[$compulsories[$table]['class']]), 'Presenter was not set.');
            }
            if (is_array($annotations)) {
                foreach ($annotations as $method => $annotation) {
                    $arguments = $this->class->getCall($annotation['service'], $annotation['method'], $annotation['parameters'], $form);
                    Assert::true(is_string($arguments) or is_null($arguments) or is_array($arguments), 'Arguments return by getCall are not set for table.column ' . $table);
                    Assert::true(is_string($annotation['service']), 'Service is not set for table.column ' . $table);
                    Assert::false(is_array($annotation['service']), 'Assigned service should be string for table.column ' . $table);
                    Assert::true(is_string($annotation['method']), 'Method is not set for table.column ' . $table);
                }
            }
        }
    }

}

id(new MockServiceTest($container))->run();
