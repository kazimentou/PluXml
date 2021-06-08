<?php

$finder = PhpCsFixer\Finder::create()
	->files()->name('*.php')
	->depth('==0')
	->in(__DIR__)
	->in(__DIR__ . '/core/{admin,lib}')
	->in(__DIR__ . '/update')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'long'],
	'declare_equal_normalize' => true,
	'concat_space'	=> ['spacing' => 'one'],
    ])
    ->setFinder($finder)
;

