<div v-pre>

# Code / Expression

Evaluates custom expressions against each item. Inline transformations and filtering without creating a dedicated node.

**Node key:** `code` · **Type:** Code

## PHP Builder

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

CodeNode::transform('{{ item.total - item.cost }}')
    ->title('Compute Margin');

CodeNode::make()
    ->mode(CodeMode::Filter)
    ->expression('{{ item.total > 100 }}');
```

See [Node Builders](../api/node-builders.md) for the conventions shared by all builders.

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `mode` | select | Yes | No | `transform` or `filter` |
| `expression` | textarea | Yes | Yes | Expression to evaluate per item |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Transformed or filtered items |
| Output | `error` | Items where evaluation failed |

## Modes

### Transform Mode

The expression result modifies the item:

- If result is an **array** → replaces the item entirely
- If result is a **scalar** → added as `_result` field, existing fields preserved

### Filter Mode

The expression result determines if the item is kept:

- **Truthy** result → item passes to `main`
- **Falsy** result → item is discarded

## Examples

**Transform — compute a value:**

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

$code = $workflow->addNode(
    CodeNode::make()
        ->title('Discount')
        ->mode(CodeMode::Transform)
        ->expression('{{ item.price * (1 - item.discount_pct / 100) }}')
);
// Output: item + {_result: 85.50}
```

**Filter — keep matching items:**

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

$code = $workflow->addNode(
    CodeNode::make()
        ->title('Adults Only')
        ->mode(CodeMode::Filter)
        ->expression('{{ item.age >= 18 && item.verified == true }}')
);
```

## Input / Output Example

**Transform mode input:**

```php
[
    ['price' => 100, 'discount_pct' => 15],
]
```

**Output:**

```php
[
    ['price' => 100, 'discount_pct' => 15, '_result' => 85],
]
```

## Complex Conditions

The Code node supports full boolean logic with `&&`, `||`, `!`, and parentheses — making it ideal for complex conditional scenarios that would otherwise require chaining multiple IF nodes.

### Nested AND / OR

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

// (active AND high-value) OR VIP customer
$code = $workflow->addNode(
    CodeNode::make()
        ->title('Qualified Lead')
        ->mode(CodeMode::Filter)
        ->expression('{{ (item.status == "active" && item.total > 500) || item.vip == true }}')
);
```

### Multiple field checks with functions

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

// Gmail users who signed up in the last 7 days and have verified email
$code = $workflow->addNode(
    CodeNode::make()
        ->title('Recent Gmail Users')
        ->mode(CodeMode::Filter)
        ->expression('{{ ends_with(item.email, "gmail.com") && date_diff(item.created_at, now(), "days") <= 7 && item.email_verified == true }}')
);
```

### Computed transform with conditions

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

// Assign tier based on multiple criteria
$code = $workflow->addNode(
    CodeNode::make()
        ->title('Assign Tier')
        ->mode(CodeMode::Transform)
        ->expression('{{ item.total > 1000 && item.orders_count > 5 ? "gold" : (item.total > 500 ? "silver" : "bronze") }}')
);
// Output: item + {_result: "gold"}
```

### Combining array functions with logic

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

// Orders with more than 3 line items totaling over $200
$code = $workflow->addNode(
    CodeNode::make()
        ->title('Large Orders')
        ->mode(CodeMode::Filter)
        ->expression('{{ count(item.line_items) > 3 && sum(pluck(item.line_items, "price")) > 200 }}')
);
```

::: tip When to use Code vs IF Condition
Use **IF Condition** for simple, single-field checks — it's easier to configure and visually clear in the editor. Use **Code** when you need `&&`, `||`, parentheses, functions, or nested ternaries. Both are safe — no `eval()` is ever used.
:::

## Tips

- No `eval()` is used — all expressions run through the safe recursive descent parser
- Use `item.field` syntax: `{{ item.quantity * item.unit_price }}`
- Expression errors route to `error` — handle malformed data gracefully
- Use parentheses to control precedence: `{{ (a || b) && c }}`
- All [38+ built-in functions](/expressions/) are available in expressions


</div>
