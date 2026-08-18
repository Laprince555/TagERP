<div class="space-y-6" dir="rtl">
    <!-- Header & Filters -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-900">لوحة المخزون</h1>
            <p class="text-sm text-gray-500 mt-1">ملخص شامل لحالة المخزون والحركات</p>
        </div>

        <!-- Warehouse Selector -->
        <div class="w-64">
            <flux:select
                wire:model.live="warehouseId"
                placeholder="اختر المستودع"
                class="w-full"
            >
                @foreach(auth()->user()->branch->warehouses as $wh)
                    <flux:option value="{{ $wh->id }}">{{ $wh->name }}</flux:option>
                @endforeach
            </flux:select>
        </div>

        <!-- Period Selector -->
        <div class="w-40">
            <flux:select
                wire:model.live="period"
                placeholder="المدة"
                class="w-full"
            >
                <flux:option value="7">آخر 7 أيام</flux:option>
                <flux:option value="30">آخر 30 يوم</flux:option>
                <flux:option value="90">آخر 3 أشهر</flux:option>
            </flux:select>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <x-stat-card
            icon="cube"
            label="المخزون الفعلي"
            value="{{ $statistics['total_on_hand'] }}"
            color="blue"
        />
        <x-stat-card
            icon="lock"
            label="المخزون المخصص"
            value="{{ $statistics['total_allocated'] }}"
            color="orange"
        />
        <x-stat-card
            icon="check"
            label="المخزون المتاح"
            value="{{ $statistics['total_available'] }}"
            color="green"
        />
        <x-stat-card
            icon="percent"
            label="معدل الاستخدام"
            value="{{ $statistics['utilization_percent'] }}%"
            color="purple"
        />
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Tables -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Low Stock Items -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <flux:icon.alert-triangle class="w-5 h-5 text-yellow-600" />
                        <h2 class="font-semibold text-gray-900">المنتجات ذات المخزون المنخفض</h2>
                        <span class="ml-auto bg-yellow-100 text-yellow-800 text-xs font-semibold px-3 py-1 rounded-full">
                            {{ $lowStockItems->count() }}
                        </span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">المنتج</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">الموقع</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">الكمية</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">الحد الأدنى</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($lowStockItems as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-gray-900 font-medium">{{ $item->item->name }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ $item->location->location_code }}</td>
                                    <td class="px-6 py-3">
                                        <span class="text-red-600 font-semibold">{{ number_format($item->qty_on_hand, 2) }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ $item->item->reorder_point ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        ✓ جميع المنتجات فوق الحد الأدنى
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Movers (High Turnover) -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <flux:icon.trending-up class="w-5 h-5 text-green-600" />
                        <h2 class="font-semibold text-gray-900">أسرع المنتجات حركة</h2>
                        <span class="ml-auto text-xs text-gray-500">آخر {{ $period }} يوم</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">المنتج</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">معدل الحركة</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-700">أيام التغطية</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($topMovers as $mover)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-gray-900 font-medium">{{ $mover['item']->name }}</td>
                                    <td class="px-6 py-3 text-green-600 font-semibold">{{ number_format($mover['velocity'], 2) }}/يوم</td>
                                    <td class="px-6 py-3">
                                        @if($mover['days_supply'] < 7)
                                            <span class="text-red-600 font-semibold">{{ round($mover['days_supply'], 1) }} يوم</span>
                                        @elseif($mover['days_supply'] < 30)
                                            <span class="text-yellow-600 font-semibold">{{ round($mover['days_supply'], 1) }} يوم</span>
                                        @else
                                            <span class="text-green-600 font-semibold">{{ round($mover['days_supply'], 1) }} يوم</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                        لا توجد حركات في هذه الفترة
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary Cards -->
        <div class="space-y-4">
            <!-- Inventory Value Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-blue-900">القيمة الإجمالية</h3>
                    <flux:icon.wallet class="w-5 h-5 text-blue-600" />
                </div>
                <p class="text-3xl font-bold text-blue-900">
                    {{ number_format($totalInventoryValue, 2) }}
                </p>
                <p class="text-xs text-blue-700 mt-1">سعر التكلفة</p>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">إجمالي الأصناف</span>
                    <span class="text-lg font-bold text-gray-900">{{ $totalItems }}</span>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <span class="text-sm text-gray-600">منتجات منخفضة</span>
                    <span class="text-lg font-bold text-red-600 block">{{ $lowStockItems->count() }}</span>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <span class="text-sm text-gray-600">منتجات مفرطة</span>
                    <span class="text-lg font-bold text-orange-600 block">{{ $overStockedItems->count() }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-2">
                <flux:button
                    href="{{ route('inventory.cycle-count.create') }}"
                    icon="clipboard"
                    variant="primary"
                    class="w-full"
                >
                    جرد دوري جديد
                </flux:button>

                <flux:button
                    href="{{ route('inventory.movements.create') }}"
                    icon="arrow-right"
                    variant="secondary"
                    class="w-full"
                >
                    إضافة حركة
                </flux:button>

                <flux:button
                    href="{{ route('inventory.reports.valuation') }}"
                    icon="chart-bar"
                    variant="secondary"
                    class="w-full"
                >
                    تقرير التقييم
                </flux:button>
            </div>
        </div>
    </div>
</div>
