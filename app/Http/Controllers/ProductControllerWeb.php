<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProductControllerWeb extends Controller
{
    protected string $baseUrl = 'https://api.ariyajanebi.ir/v1/front';

    public function index(Request $request)
    {
        $page     = (int) $request->get('page', 1);
        $query    = trim((string) $request->get('q', ''));
        $category = trim((string) $request->get('category', '')); // می‌تواند اسلاگ یا id باشد

        // 1) دریافت دسته‌ها + ساخت مپ‌ها (id=>{name,slug} و slug=>{id,name})
        [$categories, $catById, $catBySlug] = $this->fetchAndMapCategories();

        // تشخیص فیلتر: اگر ورودی اسلاگ باشد، همان؛ اگر عدد باشد، id
        $filterId   = null;
        $filterSlug = null;
        if ($category !== '') {
            if (ctype_digit($category)) {
                $filterId = (int) $category;
                // اگر در مپ موجود بود اسلاگش را هم داشته باشیم
                if (isset($catById[$filterId]['slug'])) {
                    $filterSlug = $catById[$filterId]['slug'];
                }
            } else {
                $filterSlug = $category;
                if (isset($catBySlug[$filterSlug]['id'])) {
                    $filterId = (int) $catBySlug[$filterSlug]['id'];
                }
            }
        }

        // 2) دریافت محصولات: چند تلاش با پارامترهای متداول
        [$products, $pagination] = $this->fetchProductsSmart($page, $query, $filterId, $filterSlug);

        // اگر کاربر فیلتر گذاشته و خروجی خالی شد، یکبار بدون فیلتر بگیر و لوکال فیلتر کن
        if ($category !== '' && empty($products)) {
            [$all, $pagination] = $this->fetchProductsSmart($page, $query, null, null);
            $products = $this->localFilterByCategory($all, $filterId, $filterSlug);
        }

        // 3) نرمال‌سازی محصول: نام دسته با نگاشت از category_id در صورت نبودن آبجکت دسته
        $products = array_map(function ($p) use ($catById) {
            return $this->normalizeProduct($p, $catById);
        }, $products);

        return view('productsWeb.index', [
            'products'   => $products,
            'pagination' => $pagination,
            'categories' => $categories,
            'query'      => $query,
            'category'   => $category, // برای وضعیت انتخاب شده‌ی UI
        ]);
    }

    public function pdf(Request $request)
    {
        $page     = (int) $request->get('page', 1);
        $query    = trim((string) $request->get('q', ''));
        $category = trim((string) $request->get('category', ''));

        [$categories, $catById, $catBySlug] = $this->fetchAndMapCategories();

        $filterId = null;
        $filterSlug = null;
        if ($category !== '') {
            if (ctype_digit($category)) {
                $filterId = (int) $category;
                if (isset($catById[$filterId]['slug'])) {
                    $filterSlug = $catById[$filterId]['slug'];
                }
            } else {
                $filterSlug = $category;
                if (isset($catBySlug[$filterSlug]['id'])) {
                    $filterId = (int) $catBySlug[$filterSlug]['id'];
                }
            }
        }

        [$products, $pagination] = $this->fetchProductsSmart($page, $query, $filterId, $filterSlug);

        if ($category !== '' && empty($products)) {
            [$all, $pagination] = $this->fetchProductsSmart($page, $query, null, null);
            $products = $this->localFilterByCategory($all, $filterId, $filterSlug);
        }

        $products = array_map(function ($p) use ($catById) {
            return $this->normalizeProduct($p, $catById);
        }, $products);

        return view('productsWeb.pdf', [
            'products' => $products,
            'pagination' => $pagination,
            'query' => $query,
            'category' => $category,
            'generatedAt' => now(),
        ]);
    }

    public function show(string $slug)
    {
        try {
            $res = Http::get("{$this->baseUrl}/products/{$slug}");
            if ($res->successful()) {
                $json    = $res->json();
                $product = data_get($json, 'data.product') ?? data_get($json, 'data') ?? $json;
                if (!$product) {
                    return redirect()->route('products.index')->with('error', 'محصول یافت نشد.');
                }

                // برای صفحه جزئیات هم نرمال‌سازی با حداقل اطلاعات دسته:
                [$categories, $catById] = $this->fetchAndMapCategories(); // اگر نشد، خالی هم باشد مشکلی نیست
                $product = $this->normalizeProduct($product, $catById);

                return view('productsWeb.show', compact('product'));
            }
        } catch (\Throwable $e) {}

        return redirect()->route('products.index')->with('error', 'خطا در دریافت جزئیات محصول.');
    }

    /* ==================== Helpers ==================== */

    /** خواندن دسته‌ها و ساخت سه خروجی: لیست برای UI، مپ بر اساس id، مپ بر اساس slug */
    protected function fetchAndMapCategories(): array
    {
        $list = [];
        try {
            $res = Http::get("{$this->baseUrl}/categories");
            if ($res->successful()) {
                $json = $res->json();
                // حالات رایج خروجی‌ها
                $raw =
                    data_get($json, 'data.categories.data') ??
                    data_get($json, 'data.categories') ??
                    data_get($json, 'data.data') ??
                    data_get($json, 'data') ??
                    data_get($json, 'categories') ??
                    data_get($json, 'items') ??
                    [];

                // فلت کردن درخت (children/children_recursive/subs/items)
                $flat = $this->flattenCategories($raw);

                // نرمال‌سازی فیلدها
                foreach ($flat as $c) {
                    $id   = data_get($c, 'id');
                    $slug = data_get($c, 'slug') ?? data_get($c, 'uri') ?? data_get($c, 'path');
                    $name = data_get($c, 'name') ?? data_get($c, 'title') ?? data_get($c, 'title_fa') ?? data_get($c, 'label') ?? 'بدون دسته';

                    if ($id === null && $slug === null) continue;

                    $list[] = ['id' => $id, 'slug' => $slug, 'name' => $name];
                }

                // یکتا
                $seen = [];
                $list = array_values(array_filter($list, function ($c) use (&$seen) {
                    $key = ($c['id'] ?? '') . '|' . ($c['slug'] ?? '');
                    if (isset($seen[$key])) return false;
                    return $seen[$key] = true;
                }));
            }
        } catch (\Throwable $e) {
            // اگر به هر دلیلی نگرفتیم، با آرایه خالی ادامه می‌دهیم
        }

        // ساخت مپ‌ها
        $byId = [];
        $bySlug = [];
        foreach ($list as $c) {
            if (!is_null($c['id']))   $byId[(int)$c['id']] = ['name' => $c['name'], 'slug' => $c['slug']];
            if (!empty($c['slug']))   $bySlug[$c['slug']]  = ['name' => $c['name'], 'id'   => $c['id']];
        }

        return [$list, $byId, $bySlug];
    }

    /** بازگشتی: فلت کردن درخت دسته‌ها */
    protected function flattenCategories($items): array
    {
        $out = [];
        if (!is_array($items)) return $out;

        foreach ($items as $it) {
            if (!is_array($it)) continue;
            $out[] = $it;

            $children = data_get($it, 'children')
                      ?? data_get($it, 'children_recursive')
                      ?? data_get($it, 'subs')
                      ?? data_get($it, 'items')
                      ?? [];
            if (is_array($children) && !empty($children)) {
                $out = array_merge($out, $this->flattenCategories($children));
            }
        }
        return $out;
    }

    /** چند تلاش برای گرفتن محصولات با پارامترهای رایج فیلتر */
    protected function fetchProductsSmart(int $page, string $query, ?int $filterId, ?string $filterSlug): array
    {
        $attempts = [];

        // جستجو: search و q
        $searchParams = ($query !== '')
            ? [['search' => $query], ['q' => $query]]
            : [[]];

        // فیلتر دسته
        $catParams = [[]];
        if (!is_null($filterId))   array_unshift($catParams, ['category_id' => $filterId]);
        if (!empty($filterSlug)) { array_unshift($catParams, ['category_slug' => $filterSlug]); $catParams[] = ['category' => $filterSlug]; }

        // ترکیب تلاش‌ها
        foreach ($searchParams as $s) {
            foreach ($catParams as $c) {
                $attempts[] = array_merge(['page' => $page], $s, $c);
            }
        }
        // حداقل یک تلاش بدون فیلتر
        $attempts[] = ['page' => $page];

        foreach ($attempts as $params) {
            try {
                $res = Http::get("{$this->baseUrl}/products", $params);
                if ($res->successful()) {
                    $json = $res->json();
                    $products = data_get($json, 'data.products.data')
                              ?? data_get($json, 'data.data')
                              ?? data_get($json, 'data')
                              ?? [];
                    $pagination = [
                        'current_page' => (int) (data_get($json, 'data.products.current_page', 1)),
                        'last_page'    => (int) (data_get($json, 'data.products.last_page',   1)),
                    ];
                    if (!empty($products) || (empty($filterId) && empty($filterSlug))) {
                        return [$products, $pagination];
                    }
                }
            } catch (\Throwable $e) {
                // تلاش بعدی
            }
        }

        return [[], ['current_page' => 1, 'last_page' => 1]];
    }

    /** فیلتر لوکال برای وقتی API فیلتر دسته را قبول نکرد */
    protected function localFilterByCategory(array $products, ?int $filterId, ?string $filterSlug): array
    {
        if (is_null($filterId) && empty($filterSlug)) return $products;

        return collect($products)->filter(function ($p) use ($filterId, $filterSlug) {
            $ids = [
                (string) data_get($p, 'category.id'),
                (string) data_get($p, 'category_id'),
                (string) data_get($p, 'categories.0.id'),
            ];
            $slugs = [
                (string) data_get($p, 'category.slug'),
                (string) data_get($p, 'category_slug'),
                (string) data_get($p, 'categories.0.slug'),
            ];

            $ok = false;
            if (!is_null($filterId))   $ok = $ok || in_array((string)$filterId, $ids, true);
            if (!empty($filterSlug))   $ok = $ok || in_array((string)$filterSlug, array_map('strval', $slugs), true);
            return $ok;
        })->values()->all();
    }


    protected function extractProductImageUrl(array $product): ?string
    {
        $candidate = data_get($product, 'image')
            ?? data_get($product, 'image_url')
            ?? data_get($product, 'thumbnail')
            ?? data_get($product, 'thumbnail_url')
            ?? data_get($product, 'cover')
            ?? data_get($product, 'cover_url')
            ?? data_get($product, 'main_image')
            ?? data_get($product, 'main_image.url')
            ?? data_get($product, 'media.0.url')
            ?? data_get($product, 'images.0.url')
            ?? data_get($product, 'images.0.path')
            ?? data_get($product, 'images.0')
            ?? data_get($product, 'gallery.0.url')
            ?? data_get($product, 'gallery.0.path')
            ?? data_get($product, 'photos.0.url')
            ?? data_get($product, 'photos.0.path');

        if (is_array($candidate)) {
            $candidate = data_get($candidate, 'url')
                ?? data_get($candidate, 'path')
                ?? data_get($candidate, 'src')
                ?? data_get($candidate, 'file');
        }

        if (!is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        $candidate = trim($candidate);
        if (Str::startsWith($candidate, ['http://', 'https://', 'data:'])) {
            return $candidate;
        }

        return rtrim('https://api.ariyajanebi.ir', '/') . '/' . ltrim($candidate, '/');
    }

    /** نرمال‌سازی نام دسته و قیمت‌های محصول و تنوع‌ها */
    protected function normalizeProduct(array $p, array $catById): array
    {
        // نام و اسلاگ دسته
        $categoryName = data_get($p, 'category.name')
                     ?? data_get($p, 'category.title')
                     ?? data_get($p, 'categories.0.name');

        $categorySlug = data_get($p, 'category.slug')
                     ?? data_get($p, 'categories.0.slug');

        // اگر فقط category_id داشت
        $cid = data_get($p, 'category_id');
        if (!$categoryName && $cid !== null && isset($catById[(int)$cid])) {
            $categoryName = $catById[(int)$cid]['name'] ?? 'بدون دسته';
            $categorySlug = $catById[(int)$cid]['slug'] ?? null;
        }
        if (!$categoryName) $categoryName = 'بدون دسته';

        // قیمت‌های محصول
        $base  = (int) data_get($p, 'price', 0);
        $final = (int) data_get($p, 'major_final_price.final_price', $base);
        $disc  = (int) data_get($p, 'major_final_price.discount', max(0, $base - $final));

        // تنوع‌ها
        $varieties = [];
        foreach ((array) data_get($p, 'varieties', []) as $v) {
            $vBase  = (int) data_get($v, 'price', $base);
            $vFinal = (int) data_get($v, 'final_price.final_price', $vBase);
            $vDisc  = (int) data_get($v, 'final_price.discount', max(0, $vBase - $vFinal));
            $v['__pricing'] = ['base' => $vBase, 'final' => $vFinal, 'discount' => $vDisc];
            $varieties[] = $v;
        }


        $p['__image_url']     = $this->extractProductImageUrl($p);
        $p['__category_name'] = $categoryName;
        $p['__category_slug'] = $categorySlug;
        $p['__pricing']       = ['base' => $base, 'final' => $final, 'discount' => $disc];
        $p['varieties']       = $varieties;

        return $p;
    }
}
