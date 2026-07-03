<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait AuditableModel
{
    public static function bootAuditableModel(): void
    {
        static::created(function (Model $model) {
            $module = $model->getAuditModule();
            $event = $model->getAuditEventPrefix() . '.created';
            $description = $model->getAuditCreatedDescription();

            self::resolveAuditService()->record(
                event: $event,
                module: $module,
                description: $description,
                auditable: $model,
                newValues: $model->getDirty(),
                source: AuditService::SOURCE_WEB,
            );
        });

        static::updated(function (Model $model) {
            if (!$model->getAuditTrackUpdates()) {
                return;
            }

            $module = $model->getAuditModule();
            $event = $model->getAuditEventPrefix() . '.updated';
            $description = $model->getAuditUpdatedDescription();
            $changed = $model->getDirty();

            $oldValues = [];
            $newValues = [];

            foreach ($changed as $key => $newValue) {
                if (in_array($key, $model->getAuditIgnoreFields(), true)) {
                    continue;
                }
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $newValue;
            }

            if (empty($newValues)) {
                return;
            }

            self::resolveAuditService()->record(
                event: $event,
                module: $module,
                description: $description,
                auditable: $model,
                oldValues: $oldValues,
                newValues: $newValues,
                source: AuditService::SOURCE_WEB,
            );
        });

        static::deleted(function (Model $model) {
            $module = $model->getAuditModule();
            $event = $model->getAuditEventPrefix() . '.deleted';
            $description = $model->getAuditDeletedDescription();

            self::resolveAuditService()->record(
                event: $event,
                module: $module,
                description: $description,
                auditable: $model,
                oldValues: $model->getAttributes(),
                source: AuditService::SOURCE_WEB,
            );
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model) {
                $module = $model->getAuditModule();
                $event = $model->getAuditEventPrefix() . '.restored';
                $description = $model->getAuditRestoredDescription();

                self::resolveAuditService()->record(
                    event: $event,
                    module: $module,
                    description: $description,
                    auditable: $model,
                    source: AuditService::SOURCE_WEB,
                );
            });
        }
    }

    private static function resolveAuditService(): AuditService
    {
        return app(AuditService::class);
    }

    protected function getAuditTrackUpdates(): bool
    {
        return true;
    }

    protected function getAuditIgnoreFields(): array
    {
        return ['updated_at'];
    }

    protected function getAuditModule(): string
    {
        if (property_exists($this, 'auditModule') && $this->auditModule !== null) {
            return $this->auditModule;
        }

        return (string) str(class_basename(static::class))
            ->plural()
            ->snake()
            ->lower()
            ->replace('_', '.');
    }

    protected function getAuditEventPrefix(): string
    {
        if (property_exists($this, 'auditEventPrefix') && $this->auditEventPrefix !== null) {
            return $this->auditEventPrefix;
        }

        return (string) str(class_basename(static::class))
            ->plural()
            ->snake()
            ->lower()
            ->replace('_', '.');
    }

    protected function getAuditCreatedDescription(): string
    {
        $name = class_basename(static::class);
        $identifier = $this->getAuditIdentifier();

        return "{$name} creado: {$identifier}";
    }

    protected function getAuditUpdatedDescription(): string
    {
        $name = class_basename(static::class);
        $identifier = $this->getAuditIdentifier();
        $changedKeys = implode(', ', array_keys($this->getDirty()));

        return "{$name} actualizado: {$identifier} ({$changedKeys})";
    }

    protected function getAuditDeletedDescription(): string
    {
        $name = class_basename(static::class);
        $identifier = $this->getAuditIdentifier();

        return "{$name} eliminado: {$identifier}";
    }

    protected function getAuditRestoredDescription(): string
    {
        $name = class_basename(static::class);
        $identifier = $this->getAuditIdentifier();

        return "{$name} restaurado: {$identifier}";
    }

    protected function getAuditIdentifier(): string
    {
        if (method_exists($this, 'getAuditDisplayName')) {
            return $this->getAuditDisplayName();
        }

        if (property_exists($this, 'name') && $this->name !== null) {
            return (string) $this->name;
        }

        if (property_exists($this, 'title') && $this->title !== null) {
            return (string) $this->title;
        }

        return "#{$this->getKey()}";
    }
}
