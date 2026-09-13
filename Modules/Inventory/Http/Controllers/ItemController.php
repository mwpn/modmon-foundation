<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use Modules\Inventory\Domain\Exceptions\ItemNotFoundException;

final class ItemController extends Controller
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function index(): View
    {
        return view('inventory::items.index', [
            'items' => $this->stock->allItems(),
        ]);
    }

    public function create(): View
    {
        return view('inventory::items.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->stock->createItem(
                $data['sku'],
                $data['name'],
                $data['unit'] ?? null,
                (bool) ($data['active'] ?? true),
            );
        } catch (\Illuminate\Database\QueryException) {
            return back()->withInput()->withErrors(['sku' => 'SKU already exists.']);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['sku' => $e->getMessage()]);
        }

        return redirect()
            ->route('inventory.items.index')
            ->with('status', 'Item created.');
    }

    public function edit(string $sku): View|RedirectResponse
    {
        $item = $this->stock->find($sku);

        if ($item === null) {
            return redirect()
                ->route('inventory.items.index')
                ->withErrors(['sku' => "Item '{$sku}' was not found."]);
        }

        return view('inventory::items.edit', [
            'item' => $item,
        ]);
    }

    public function update(Request $request, string $sku): RedirectResponse
    {
        // SKU is immutable: route {sku} is identity; request body sku is ignored.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->stock->updateItem(
                $sku,
                $data['name'],
                $data['unit'] ?? null,
                $request->boolean('active', true),
            );
        } catch (ItemNotFoundException $e) {
            return redirect()->route('inventory.items.index')->withErrors(['sku' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('inventory.items.edit', ['sku' => $sku])
            ->with('status', 'Item updated.');
    }

    public function adjust(Request $request, string $sku): RedirectResponse
    {
        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->stock->adjust($sku, (int) $data['delta'], $data['reason']);
        } catch (ItemNotFoundException $e) {
            return redirect()->route('inventory.items.index')->withErrors(['sku' => $e->getMessage()]);
        } catch (InvalidStockAdjustmentException $e) {
            return back()->withInput()->withErrors(['delta' => $e->getMessage()]);
        }

        return redirect()
            ->route('inventory.items.edit', ['sku' => $sku])
            ->with('status', 'Stock adjusted.');
    }
}
