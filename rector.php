<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\DowngradePhp74\Rector\Array_\DowngradeArraySpreadRector;
use Rector\DowngradePhp74\Rector\ClassMethod\DowngradeContravariantArgumentTypeRector;
use Rector\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
	// get parameters
	$parameters = $containerConfigurator->parameters();
	$parameters->set(Option::PATHS, [
		__DIR__ . '/src'
	]);

	$parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_73);
	$parameters->set(Option::AUTO_IMPORT_NAMES, true);

	// Define what rule sets will be applied
	$containerConfigurator->import(SetList::PHP_73);

	//get services (needed for register a single rule)
	$services = $containerConfigurator->services();

	//register a single rule
	$services->set(DowngradeTypedPropertyRector::class);
};
