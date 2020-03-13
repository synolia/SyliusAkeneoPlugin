<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

use Doctrine\Common\Annotations\AnnotationReader;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class EntityObjectToArrayTransformer
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function entityObjectToArray(ResourceInterface $object): ?array
    {
        $normalizer = new ObjectNormalizer(
            new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())),
            new CamelCaseToSnakeCaseNameConverter(),
        );

        /** @var string $className */
        $className = strrchr(get_class($object), '\\');
        $classNameAsProperty = lcfirst(substr($className, 1));
        $normalizer->setSerializer($this->serializer);
        $result = $normalizer->normalize($object, null, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => [$classNameAsProperty, 'id'],
        ]);

        if (!is_array($result)) {
            return null;
        }

        return $result;
    }
}
