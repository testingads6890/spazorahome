<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Renders every admin "create" form, fills in the fields it actually contains and submits it.
 * Catches forms whose fields do not match what the controller validates.
 */
class AdminFormSubmitTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_create_form_can_be_submitted_with_its_own_fields(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x',
            'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
        ]);

        $failures = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            // The shipment form needs a placed order; CartCheckoutTest covers it.
            if (! $name || ! str_starts_with($name, 'admin.') || ! str_ends_with($name, '.create') || $name === 'admin.orders.shipping.create') {
                continue;
            }
            // Nested create forms (a product's variants, an attribute's swatches) are opened on an existing parent.
            $params = $this->firstModels($route);
            if ($params === null) {
                $failures[] = "$name: no parent record";
                continue;
            }

            $html = $this->actingAs($admin)->get(route($name, $params))->getContent();
            [$action, $payload] = $this->parseForm($html);
            if (! $action) {
                continue;
            }

            try {
                $response = $this->withoutExceptionHandling()->actingAs($admin)->post($action, $payload);
                $errors = session('errors') ? session('errors')->keys() : [];
                if ($response->getStatusCode() >= 400 || $errors) {
                    $failures[] = "$name: status {$response->getStatusCode()} errors [" . implode(',', $errors) . '] payload keys [' . implode(',', array_keys($payload)) . ']';
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                $failures[] = "$name: validation " . json_encode($e->errors());
            } catch (\Throwable $e) {
                $failures[] = "$name: " . strtok($e->getMessage(), "\n");
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_other_admin_settings_forms_can_be_submitted_with_their_own_fields(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        \App\Models\Language::create(['name' => 'English', 'code' => 'en', 'direction' => 'ltr', 'is_active' => true]);

        $failures = [];
        foreach (['admin.settings.index', 'admin.seo.index', 'admin.theme.index', 'admin.marketplace.index', 'admin.translations.index', 'admin.permissions.create-role', 'admin.permissions.create-permission'] as $name) {
            $html = $this->actingAs($admin)->get(route($name))->getContent();
            [$action, $payload] = $this->parseForm($html);
            if (! $action) {
                $failures[] = "$name: no POST form found";
                continue;
            }

            try {
                $response = $this->withoutExceptionHandling()->actingAs($admin)->post($action, $payload);
                if ($response->getStatusCode() >= 400) {
                    $failures[] = "$name: status {$response->getStatusCode()}";
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                $failures[] = "$name: validation " . json_encode($e->errors()) . ' payload ' . json_encode($payload);
            } catch (\Throwable $e) {
                $failures[] = "$name: " . strtok($e->getMessage(), "
");
            }
        }

        $this->assertSame([], $failures, implode("
", $failures));
    }

    /**
     * Opens every admin "edit" form on a real record and submits it back with the values it shows.
     * Catches edit forms whose fields, method or route do not match what the controller expects.
     */
    public function test_every_admin_edit_form_can_be_submitted_with_its_own_fields(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Category::create(['name' => 'Sofas', 'slug' => 'sofas']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Sofa', 'slug' => 'sofa', 'description' => 'x',
            'sku' => 'S1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Chair', 'slug' => 'chair', 'description' => 'x',
            'sku' => 'C1', 'price' => 10, 'stock_quantity' => 1, 'is_active' => true,
        ]);
        \App\Models\Review::create(['user_id' => $admin->id, 'product_id' => $product->id, 'rating' => 4, 'title' => 'Good', 'comment' => 'Nice sofa', 'is_approved' => false]);

        // First create one record of everything through the create forms.
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with((string) $r->getName(), 'admin.'));
        foreach ($routes->filter(fn ($r) => str_ends_with($r->getName(), '.create')) as $route) {
            $params = $this->firstModels($route);
            if ($params === null) {
                continue;
            }
            [$action, $payload] = $this->parseForm($this->actingAs($admin)->get(route($route->getName(), $params))->getContent());
            if ($action) {
                $this->actingAs($admin)->post($action, $payload);
            }
        }

        $failures = [];
        $checked = 0;
        foreach ($routes->filter(fn ($r) => preg_match('/\.edit(-|$)/', $r->getName())) as $route) {
            $name = $route->getName();
            $params = $this->firstModels($route);
            if ($params === null) {
                $failures[] = "$name: no record to edit";
                continue;
            }

            $page = $this->actingAs($admin)->get(route($name, $params));
            if ($page->getStatusCode() !== 200) {
                $failures[] = "$name: edit page status {$page->getStatusCode()}";
                continue;
            }
            [$action, $payload] = $this->parseForm($page->getContent());
            if (! $action) {
                $failures[] = "$name: no form found";
                continue;
            }

            try {
                $response = $this->withoutExceptionHandling()->actingAs($admin)->post($action, $payload);
                $errors = session('errors') ? session('errors')->keys() : [];
                if ($response->getStatusCode() >= 400 || $errors) {
                    $failures[] = "$name: status {$response->getStatusCode()} errors [" . implode(',', $errors) . ']';
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                $failures[] = "$name: validation " . json_encode($e->errors());
            } catch (\Throwable $e) {
                $failures[] = "$name: " . strtok($e->getMessage(), "\n");
            }
            $checked++;

            // Second pass: untick every checkbox. An unticked box sends nothing, so the form must still switch the value off.
            [$action, $payload] = $this->parseForm($this->actingAs($admin)->get(route($name, $params))->getContent(), untick: true);
            $this->actingAs($admin)->post($action, $payload);
            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="utf-8"?>' . $this->actingAs($admin)->get(route($name, $params))->getContent());
            foreach ((new DOMXPath($doc))->query('//form//input[@type="checkbox"][@checked][not(contains(@name, "["))]') as $box) {
                $failures[] = "$name: checkbox {$box->getAttribute('name')} cannot be switched off";
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
        $this->assertGreaterThanOrEqual(20, $checked);
    }

    /** First existing record for each model a route binds, or null when one is missing. */
    private function firstModels($route): ?array
    {
        $params = [];
        foreach ($route->signatureParameters(['subClass' => \Illuminate\Database\Eloquent\Model::class]) as $parameter) {
            $class = $parameter->getType()->getName();
            $model = $class::query()->first();
            if (! $model) {
                return null;
            }
            // Resource routes name the parameter in snake case ({room_idea}) while the controller uses $roomIdea.
            $key = in_array($parameter->getName(), $route->parameterNames(), true) ? $parameter->getName() : \Illuminate\Support\Str::snake($parameter->getName());
            $params[$key] = $model;
        }
        // Parameters bound by name only (e.g. {role}) are not covered by type hints.
        foreach ($route->parameterNames() as $name) {
            if (! isset($params[$name])) {
                if ($name !== 'role' || ! ($role = Role::where('name', 'customer')->first())) {
                    return null;
                }
                $params[$name] = $role->id;
            }
        }

        return $params;
    }

    /** @return array{0: ?string, 1: array} form action and a payload filled from the form's own fields */
    private function parseForm(string $html, bool $untick = false): array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xp = new DOMXPath($doc);

        $form = null;
        foreach ($xp->query('//form[@method]') as $f) {
            if (strtoupper($f->getAttribute('method')) === 'POST' && ! str_contains($f->getAttribute('action'), 'logout')) {
                $form = $f;
                break;
            }
        }
        if (! $form) {
            return [null, []];
        }

        $payload = [];
        foreach ($xp->query('.//input|.//select|.//textarea', $form) as $el) {
            $n = $el->getAttribute('name');
            if ($n === '' || $n === '_token') {
                continue;
            }
            $type = strtolower($el->getAttribute('type') ?: $el->nodeName);
            $value = match (true) {
                $n === 'hsn_code' => '9403',
                $n === 'attributes' => "Colour: Red\nSize: Large",
                $n === 'links' => "Sale | /search\nBlog | https://example.com/blog",
                str_ends_with($n, '[image]') => 'https://example.com/slide.jpg',
                str_ends_with($n, '[link]') => '/search',
                in_array($n, ['link', 'url', 'website', 'cta_link'], true) => 'https://example.com/shop',
                $n === 'code' => 'XX',
                $n === 'symbol' => 'Rs',
                // Location dropdowns fill in with JavaScript; the test chooses a real India location instead.
                in_array($n, ['country_id', 'state_id', 'district_id', 'city_id', 'pincode_id'], true) => (string) $this->dispatchLocation()[$n],
                str_starts_with($n, 'related') && $el->nodeName === 'select' => $this->firstOption($xp, $el, 1),
                $el->nodeName === 'select' => $this->firstOption($xp, $el),
                $type === 'checkbox' => $untick ? null : ($el->getAttribute('value') ?: '1'),
                $type === 'file', $type === 'submit', $type === 'button' => null,
                $type === 'number' => $el->getAttribute('value') !== '' ? $el->getAttribute('value') : '5',
                $type === 'email' => 'test@example.com',
                $type === 'url' => 'https://example.com',
                $type === 'date' => now()->addDays(str_contains($n, 'end') ? 30 : 1)->format('Y-m-d'),
                $type === 'datetime-local' => now()->addDays(str_contains($n, 'end') ? 30 : 1)->format('Y-m-d\TH:i'),
                $type === 'color' => '#aabbcc',
                $type === 'hidden' => $el->getAttribute('value'),
                $el->nodeName === 'textarea' && trim($el->textContent) !== '' => $el->textContent,
                default => $el->getAttribute('value') !== '' ? $el->getAttribute('value') : 'Test ' . str_replace(['[', ']'], '', $n) . uniqid(),
            };
            if ($value === null) {
                continue;
            }
            str_ends_with($n, '[]') ? $payload[substr($n, 0, -2)][] = $value : $payload[$n] = $value;
        }

        return [$form->getAttribute('action'), $payload];
    }

    private function firstOption(DOMXPath $xp, $select, int $skip = 0): ?string
    {
        $options = iterator_to_array($xp->query('.//option[@value!=""]', $select));
        $selected = $xp->query('.//option[@selected and @value!=""]', $select)->item(0);
        $opt = $selected ?? $options[$skip] ?? $options[0] ?? null;

        return $opt?->getAttribute('value');
    }

    public function test_translations_page_does_not_redirect_to_itself_without_a_language(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.translations.index'))->assertRedirect(route('admin.languages.index'));
    }
}
