<?php namespace XREmitter\Tests;
use \PHPUnit_Framework_TestCase as PhpUnitTestCase;
use \XREmitter\Events\Event as Event;
use \Locker\XApi\Statement as Statement;

abstract class EventTest extends PhpUnitTestCase {
    protected static $xapiType = 'http://lrs.learninglocker.net/define/type/moodle/';
    protected static $recipeName;

    /**
     * Sets up the tests.
     * @override PhpUnitTestCase
     */
    public function setup() {}

    /**
     * Tests the read method of the Event.
     */
    public function testRead() {
        $input = $this->constructInput();
        $output = $this->event->read($input);
        $this->assertOutput($input, $output);
        $this->createExampleFile($output);
    }

    protected function constructInput() {
        return array_merge(
            $this->constructUser('user'),
            $this->constructLog(),
            $this->constructApp(),
            $this->constructSource(),
            ['recipe' => static::$recipeName]
        );
    }

    protected function constructUser($type) {
        return [
            $type.'_id' => '1',
            $type.'_url' => 'http://www.example.com/'.$type.'_url',
            $type.'_name' => 'Test '.$type.'_name',
        ];
    }

    private function constructLog() {
        return [
            'context_lang' => 'en',
            'context_platform' => 'Moodle',
            'context_info' => (object) [
                'https://moodle.org/' => '1.0.0'
            ],
            'context_ext' => [
                'test_context_ext_key' => 'test_context_ext_value',
            ],
            'context_ext_key' => 'http://www.example.com/context_ext_key',
            'time' => '2015-01-01T01:00Z',
        ];
    }

    protected function contructObject($type, $xapiType = null) {
        if (is_null($xapiType)){
            $xapiType = static::$xapiType.$type;
        }
        return [
            $type.'_url' => 'http://www.example.com/'.$type.'_url',
            $type.'_name' => 'Test '.$type.'_name',
            $type.'_description' => 'Test '.$type.'_description',
            $type.'_type' => $xapiType,
            $type.'_ext' => [
                'test_'.$type.'_ext_key' => 'test_'.$type.'_ext_value',
            ],
            $type.'_ext_key' => 'http://www.example.com/'.$type.'_ext_key',
        ];
    }

    protected function constructApp() {
        $type = 'app';
        return [
            $type.'_url' => 'http://www.example.com/'.$type.'_url',
            $type.'_name' => 'Test '.$type.'_name',
            $type.'_description' => 'Test '.$type.'_description',
            $type.'_type' => 'http://id.tincanapi.com/activitytype/site',
            $type.'_ext' => [
                'test_'.$type.'_ext_key' => 'test_'.$type.'_ext_value',
            ],
            $type.'_ext_key' => 'http://www.example.com/'.$type.'_ext_key',
        ];
    }

    protected function constructSource() {
        $type = 'source';
        return [
            $type.'_url' => 'http://www.example.com/'.$type.'_url',
            $type.'_name' => 'Test '.$type.'_name',
            $type.'_description' => 'Test '.$type.'_description',
            $type.'_type' => 'http://id.tincanapi.com/activitytype/source'
        ];
    }

    protected function constructAttempt() {
        return [
            'attempt_url' => 'http://www.example.com/attempt_url',
            'attempt_type' => static::$xapiType.'attempt',
            'attempt_ext' => [
                'test_attempt_ext_key' => 'test_attempt_ext_value',
            ],
            'attempt_ext_key' => 'http://www.example.com/attempt_ext_key',
            'attempt_name' => 'Test attempt_name',
        ];
    }

    protected function constructDiscussion() {
        return [
            'discussion_url' => 'http://www.example.com/discussion_url',
            'discussion_name' => 'A Forum Post',
            'discussion_description' => 'A description of the forum',
            'discussion_type' => static::$xapiType.'discussion',
            'discussion_ext_key' => 'http://www.example.com/attempt_ext_key',
            'discussion_ext' => [
                'discussion_ext_key' => 'discussion_ext_value',
            ],
        ];
    }

    protected function assertOutput($input, $output) {
        $this->assertUser($input, $output['actor'], 'user');
        $this->assertObject('app', $input, $output['context']['contextActivities']['grouping'][0]);
        $this->assertObject('source', $input, $output['context']['contextActivities']['category'][0]);
        $this->assertLog($input, $output);
        $this->assertInfo(
            $input['context_info'],
            $output['context']['extensions']['http://lrs.learninglocker.net/define/extensions/info']
        );
        $this->assertValidXapiStatement($output);
    }

    protected function assertValidXapiStatement($output) {
        $errors = Statement::createFromJson(json_encode($output))->validate();
        $errorsJson = json_encode(array_map(function ($error) {
            return (string) $error;
        }, $errors));
        $this->assertEmpty($errors, $errorsJson);
    }

    protected function assertInfo($input, $output) {
        $this->assertEquals(
            $input->{'https://moodle.org/'},
            $output->{'https://moodle.org/'}
        );
    }

    protected function assertUser($input, $output, $type) {
        $this->assertEquals($input[$type.'_id'], $output['account']['name']);
        $this->assertEquals($input[$type.'_url'], $output['account']['homePage']);
        $this->assertEquals($input[$type.'_name'], $output['name']);
    }

    protected function assertLog($input, $output) {
        $actualContext = $output['context'];
        $this->assertEquals($input['context_lang'], $actualContext['language']);
        $this->assertEquals($input['context_platform'], $actualContext['platform']);
        $this->assertArrayHasKey($input['context_ext_key'], $actualContext['extensions']);
        $this->assertEquals($input['context_ext'], $actualContext['extensions'][$input['context_ext_key']]);
        $this->assertEquals($input['time'], $output['timestamp']);
    }

    protected function assertObject($type, $input, $output) {
        $this->assertEquals($input[$type.'_url'], $output['id']);
        $this->assertEquals($input[$type.'_name'], $output['definition']['name'][$input['context_lang']]);
        $this->assertEquals($input[$type.'_type'], $output['definition']['type']);
        $this->assertEquals($input[$type.'_description'], $output['definition']['description'][$input['context_lang']]);
    }

    protected function assertVerb($verbId, $verbName, $output) {
        $this->assertEquals($verbId, $output['id']);
        $this->assertEquals($verbName, $output['display']['en']);
    }

    protected function assertAttempt($input, $output) {
        $this->assertEquals($input['attempt_url'], $output['id']);
        $this->assertEquals($input['attempt_name'], $output['definition']['name'][$input['context_lang']]);
        $this->assertEquals($input['attempt_type'], $output['definition']['type']);
        $this->assertArrayHasKey($input['attempt_ext_key'], $output['definition']['extensions']);
        $this->assertEquals($input['attempt_ext'], $output['definition']['extensions'][$input['attempt_ext_key']]);
    }

    protected function assertComponentList($input, $output, $lang) {
        foreach ($input as $id => $description) {
            $outputId = 'Matching Id not found.';
            $outputDescription = null;
            foreach ($output as $outputItem) {
                if ($outputItem->id == $id) {
                    $outputId = $outputItem->id;
                    $outputDescription = $outputItem->description[$lang];
                }
            }
            $this->assertEquals($id, $outputId);
            $this->assertEquals($description, $outputDescription);
        }
    }

    protected function createExampleFile($output) {
        $classArray = explode('\\', get_class($this));
        $eventName = str_replace('Test', '', array_pop($classArray));
        $exampleFile = __DIR__.'/../docs/examples/'.$eventName.'.json';
        file_put_contents($exampleFile, json_encode($output, JSON_PRETTY_PRINT));
    }
}
