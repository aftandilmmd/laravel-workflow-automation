<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->target = sys_get_temp_dir().'/workflow-builders-'.Str::random(8);
});

afterEach(function () {
    File::deleteDirectory($this->target);
});

it('generates a builder from a node schema', function () {
    $this->artisan('workflow:make-node-builder', [
        'node'        => 'parse_data',
        '--path'      => $this->target,
        '--namespace' => 'App\\Workflows\\Builders',
    ])->assertSuccessful();

    $file = $this->target.'/ParseDataNode.php';

    expect(File::exists($file))->toBeTrue();

    $contents = File::get($file);

    expect($contents)
        ->toContain('namespace App\\Workflows\\Builders;')
        ->toContain('class ParseDataNode extends NodeDefinition')
        ->toContain("return 'parse_data';")
        ->toContain('public function sourceField(string $value): static')
        ->toContain('public function format(string $value): static')
        ->toContain('public function targetField(string $value): static')
        ->toContain('Allowed values: json, csv, key_value.')
        ->toContain('Supports expressions');
});

it('types boolean, integer and array fields', function () {
    $this->artisan('workflow:make-node-builder', ['node' => 'http_request', '--path' => $this->target])
        ->assertSuccessful();

    expect(File::get($this->target.'/HttpRequestNode.php'))
        ->toContain('public function includeResponse(bool $value = true): static')
        ->toContain('public function timeout(int $value): static')
        ->toContain('public function headers(array $value): static');
});

it('fails for an unknown node', function () {
    $this->artisan('workflow:make-node-builder', ['node' => 'nope', '--path' => $this->target])
        ->expectsOutputToContain('Node [nope] is not registered.')
        ->assertFailed();
});

it('refuses to overwrite without --force', function () {
    $this->artisan('workflow:make-node-builder', ['node' => 'loop', '--path' => $this->target])->assertSuccessful();

    $this->artisan('workflow:make-node-builder', ['node' => 'loop', '--path' => $this->target])->assertFailed();

    $this->artisan('workflow:make-node-builder', ['node' => 'loop', '--path' => $this->target, '--force' => true])
        ->assertSuccessful();
});

it('produces a class that resolves through the registry', function () {
    $this->artisan('workflow:make-node-builder', [
        'node'        => 'merge',
        '--path'      => $this->target,
        '--namespace' => 'Tests\\Generated',
        '--class'     => 'GeneratedMergeNode',
    ])->assertSuccessful();

    require $this->target.'/GeneratedMergeNode.php';

    $definition = (new Tests\Generated\GeneratedMergeNode)->mode('zip');

    expect($definition->nodeKey())->toBe('merge')
        ->and($definition->getConfig())->toBe(['mode' => 'zip']);

    $definition->validate();
});
