<?php
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Nette\Set\NetteSetList;
use Rector\Config\RectorConfig;

return function (RectorConfig $rectorConfig): void {
    $rectorConfig->disableParallel();
//    $rectorConfig->sets([
////    DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
//        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
////        NetteSetList::ANNOTATIONS_TO_ATTRIBUTES,
////        SensiolabsSetList::FRAMEWORK_EXTRA_61,
//    ]);
    $rectorConfig->rule(\Rector\Symfony\Symfony61\Rector\Class_\CommandPropertyToAttributeRector::class);
};
