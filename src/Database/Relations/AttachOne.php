<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;
use Winter\Storm\Database\Attach\File as FileModel;

/**
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class AttachOne extends MorphOneBase implements RelationInterface
{
    use Concerns\AttachOneOrMany;
    use Concerns\CanBeCounted;
    use Concerns\CanBeDependent;
    use Concerns\CanBeExtended;
    use Concerns\CanBePushed;
    use Concerns\CanBeSoftDeleted;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * Create a new attach one relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  bool  $isPublic
     * @param  string  $localKey
     * @param  string  $fieldName
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $fieldName)
    {
        $this->fieldName = $fieldName;
        parent::__construct($query, $parent, $type, $id, $localKey);
        $this->public = $isPublic;
        $this->extendableRelationConstruct();
    }

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        /*
         * Newly uploaded file
         */
        if ($this->isValidFileData($value)) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $file = $this->create(['data' => $value]);
                $this->parent->setRelation($this->relationName, $file);
            });
        }
        /*
         * Existing File model
         */
        elseif ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->add($value);
            });
        }

        /*
         * The relation is set here to satisfy `getValidationValue`
         */
        $this->parent->setRelation($this->relationName, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getSimpleValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            return $value->getPath();
        }

        return null;
    }

    /**
     * Helper for getting this relationship validation value.
     */
    public function getValidationValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            return $this->makeValidationFile($value);
        }

        return null;
    }

    /**
     * Internal method used by `getSimpleValue` and `getValidationValue`
     */
    protected function getSimpleValueInternal()
    {
        $value = null;

        $file = ($sessionKey = $this->parent->sessionKey)
            ? $this->withDeferred($sessionKey)->first()
            : $this->parent->{$this->relationName};

        if ($file) {
            $value = $file;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayDefinition(): array
    {
        return [
            get_class($this->query->getModel()),
            'key' => $this->localKey,
            'delete' => $this->isDependent(),
            'public' => $this->public,
            'push' => $this->isPushable(),
            'count' => $this->isCountOnly(),
        ];
    }
}
