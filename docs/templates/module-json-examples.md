# module.json Examples

## Minimal Business Module (No Dependencies)

```json
{
    "schema": 1,
    "name": "Customer",
    "code": "customer",
    "version": "1.0.0",
    "type": "business",
    "provider": "Modules\\Customer\\CustomerServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": []
    },
    "provides": [
        "customer.management"
    ]
}
```

## Platform Module (Identity)

```json
{
    "schema": 1,
    "name": "Identity",
    "code": "identity",
    "version": "1.0.0",
    "type": "platform",
    "provider": "Modules\\Identity\\IdentityServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": []
    },
    "provides": [
        "identity.user",
        "identity.authentication"
    ]
}
```

## Business Module with Dependencies

```json
{
    "schema": 1,
    "name": "Inventory",
    "code": "inventory",
    "version": "1.0.0",
    "type": "business",
    "provider": "Modules\\Inventory\\InventoryServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": ["catalog.product"]
    },
    "provides": [
        "inventory.stock",
        "inventory.transfer"
    ]
}
```

## Integration Module

```json
{
    "schema": 1,
    "name": "Telegram Notifications",
    "code": "telegram",
    "version": "1.0.0",
    "type": "integration",
    "provider": "Modules\\Telegram\\TelegramServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": []
    },
    "provides": [
        "notification.telegram"
    ]
}
```

## SaaS Platform Module (Tenancy)

```json
{
    "schema": 1,
    "name": "Tenancy",
    "code": "tenancy",
    "version": "1.0.0",
    "type": "platform",
    "provider": "Modules\\Tenancy\\TenancyServiceProvider",
    "compatibility": {
        "php": "^8.3",
        "laravel": "^13.0",
        "foundation": "^1.0"
    },
    "requires": {
        "capabilities": ["identity.user"]
    },
    "provides": [
        "tenancy.tenant",
        "tenancy.organization"
    ]
}
```

## Field Reference

| Field                      | Required | Type      | Validation                                          |
|----------------------------|----------|-----------|-----------------------------------------------------|
| `schema`                   | yes      | integer   | Positive integer (currently `1`).                   |
| `name`                     | yes      | string    | Non-empty human-readable name.                      |
| `code`                     | yes      | string    | `^[a-z][a-z0-9\-]*$`                                |
| `version`                  | yes      | string    | `^\d+\.\d+\.\d+`                                    |
| `provider`                 | yes      | string    | Fully-qualified class name (contains `\`).          |
| `type`                     | no       | string    | `platform`, `business`, or `integration`. Default: `business`. |
| `compatibility`            | no       | object    | Contains `php`, `laravel`, `foundation` constraints.|
| `compatibility.php`        | no       | string    | Composer semver constraint.                         |
| `compatibility.laravel`    | no       | string    | Composer semver constraint.                         |
| `compatibility.foundation` | no       | string    | Composer semver constraint.                         |
| `requires`                 | no       | object    | Contains `capabilities` array.                      |
| `requires.capabilities`    | no       | array     | Array of capability identifier strings.             |
| `provides`                 | no       | array     | Array of capability identifier strings.             |
