<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage menus');
    }

    public function index()
    {
        $menus = Menu::with(['items' => function ($query) {
            $query->whereNull('parent_id')
                ->orderBy('order')
                ->with(['children' => function ($q) {
                    $q->orderBy('order');
                }]);
        }])->get();

        $pages = Page::select('id', 'title', 'slug')->get();
        $posts = Post::select('id', 'title', 'slug')->get();

        return Inertia::render('Admin/Menus/Index', [
            'menus' => $menus->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'location' => $menu->location,
                    'description' => $menu->description,
                    'is_active' => $menu->is_active,
                    'items' => $this->transformMenuItems($menu->items),
                ];
            }),
            'pages' => $pages,
            'posts' => $posts,
        ]);
    }

    protected function transformMenuItems($items)
    {
        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'type' => $item->type,
                'target' => $item->target,
                'order' => $item->order,
                'children' => $this->transformMenuItems($item->children),
            ];
        });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $menu = Menu::create($validated);

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu created successfully.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $menu->update($validated);

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu deleted successfully.');
    }

    public function storeMenuItem(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:menu_items,id',
            'type' => 'required|string|in:custom,page,post,home',
            'target' => 'nullable|string|in:_self,_blank',
            'order' => 'nullable|integer',
            'attributes' => 'nullable|array',
        ]);

        $validated['order'] = $menu->items()->max('order') + 1;

        $menuItem = $menu->items()->create($validated);

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu item added successfully.');
    }

    /**
     * Create many top-level menu items in one request (avoids N sequential HTTP round-trips).
     */
    public function storeMenuItemsBulk(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'items' => 'required|array|max:100',
            'items.*.title' => 'required|string|max:255',
            'items.*.url' => 'required|string|max:255',
            'items.*.type' => 'required|string|in:custom,page,post,home',
            'items.*.target' => 'nullable|string|in:_self,_blank',
        ]);

        $order = (int) $menu->items()->max('order');

        DB::transaction(function () use ($menu, $validated, &$order) {
            foreach ($validated['items'] as $item) {
                $order++;
                $menu->items()->create([
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'type' => $item['type'],
                    'target' => $item['target'] ?? '_self',
                    'order' => $order,
                ]);
            }
        });

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu items added successfully.');
    }

    public function updateMenuItem(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|string|max:255',
            'parent_id' => 'sometimes|nullable|exists:menu_items,id',
            'type' => 'sometimes|required|string|in:custom,page,post,home',
            'target' => 'sometimes|nullable|string|in:_self,_blank',
            'order' => 'sometimes|nullable|integer',
            'attributes' => 'sometimes|nullable|array',
        ]);

        $menuItem->update($validated);

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu item updated successfully.');
    }

    public function destroyMenuItem(MenuItem $menuItem)
    {
        $menu = $menuItem->menu;
        $menuItem->delete();

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu item deleted successfully.');
    }

    public function reorderMenuItems(Request $request, Menu $menu)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.order' => 'required|integer',
            'items.*.parent_id' => 'nullable|exists:menu_items,id',
        ]);

        \Log::info('Reordering menu items request:', $request->all());

        foreach ($request->items as $item) {
            try {
                // Log the item being processed
                \Log::info('Processing item:', $item);

                // Ambil item menu untuk pemrosesan
                $menuItem = MenuItem::find($item['id']);

                if (! $menuItem) {
                    \Log::error('Menu item not found:', ['id' => $item['id']]);

                    continue;
                }

                // Catat status awal
                \Log::info('Original state:', [
                    'id' => $menuItem->id,
                    'parent_id' => $menuItem->parent_id,
                    'order' => $menuItem->order,
                ]);

                // Pastikan parent_id adalah null atau ada di database
                $parentId = $item['parent_id'];

                // Jika parent_id null, pastikan untuk mengubah parent_id menjadi null
                if ($parentId === null) {
                    \Log::info('Setting parent_id to null for item:', ['id' => $item['id']]);
                    $menuItem->parent_id = null;
                    $menuItem->order = $item['order'];
                    $menuItem->save();
                } else {
                    // Validasi parent_id ada
                    $parentExists = MenuItem::where('id', $parentId)
                        ->where('menu_id', $menu->id)
                        ->exists();

                    if (! $parentExists) {
                        \Log::warning('Parent does not exist, setting parent_id to null:', [
                            'item_id' => $item['id'],
                            'parent_id' => $parentId,
                        ]);
                        $parentId = null;
                    }

                    $menuItem->parent_id = $parentId;
                    $menuItem->order = $item['order'];
                    $menuItem->save();
                }

                // Log hasil akhir update
                \Log::info('Updated item:', [
                    'id' => $menuItem->id,
                    'new_parent_id' => $menuItem->parent_id,
                    'new_order' => $menuItem->order,
                ]);

            } catch (\Exception $e) {
                \Log::error('Error updating menu item:', [
                    'item' => $item,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return redirect()->back()
            ->with('success', 'Menu items reordered successfully.');
    }
}
