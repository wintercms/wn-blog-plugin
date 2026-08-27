# The Complete Markdown Showcase

This scaffolded post deliberately exercises **every** common Markdown construct so the
editor's split preview, the rendered `content_html`, and the front-end all have
something meaty to render — especially useful when auditing the backend in **dark mode**.

## Emphasis, links and inline code

You can write *italic*, **bold**, ***bold italic***, ~~strikethrough~~ and `inline code`.
Here is [a link to the Winter CMS docs](https://wintercms.com/docs) and an autolink:
https://wintercms.com.

<!-- more -->

## Lists

Unordered:

- A first bullet with a reasonably long line so we can see how wrapping behaves inside list items in the preview pane
- A second bullet
  - A nested bullet
  - Another nested bullet
- A third bullet

Ordered:

1. Install Winter CMS
2. Enable the TailwindUI skin
3. Toggle dark mode
4. Audit everything

## Blockquote

> Design is not just what it looks like and feels like. Design is how it works.
> — someone who never had to audit contrast ratios.

## Code block

```php
public function handle(): int
{
    if ($this->getLaravel()->environment('production')) {
        $this->error('Refusing to scaffold in production.');
        return self::FAILURE;
    }

    return self::SUCCESS;
}
```

## Table

| Surface        | Light mode | Dark mode | Notes                          |
| -------------- | ---------- | --------- | ------------------------------ |
| List rows      | OK         | Check     | zebra striping + hover         |
| Form tabs      | OK         | Check     | active tab contrast            |
| Markdown preview | OK       | Check     | code blocks & tables get dark  |
| Popups         | OK         | Check     | backdrop + border              |

## Image

![Winter CMS](https://raw.githubusercontent.com/wintercms/winter/develop/modules/backend/assets/images/winter-logo.svg)

---

That's the end of the showcase. If any of the above renders with unstyled (flash-bang
white) or low-contrast content in dark mode, it's a finding.
