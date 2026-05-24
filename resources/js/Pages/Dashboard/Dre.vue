<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    report: Object,
    selectedYear: Number,
    selectedMonth: Number,
    availableMonths: Array,
});

const selectedYear = ref(props.selectedYear);
const selectedMonth = ref(props.selectedMonth);

const formatCurrency = (cents) => {
    if (cents === null || cents === undefined) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
};

const formatMonthName = (month) => {
    const date = new Date(2000, month - 1, 1);
    return date.toLocaleDateString('pt-BR', { month: 'long' });
};

const profitClass = (value) => {
    if (value > 0) return 'text-green-600';
    if (value < 0) return 'text-red-600';
    return 'text-gray-900';
};

const marginClass = (value) => {
    if (value >= 30) return 'text-green-600 bg-green-50 border border-green-200';
    if (value >= 10) return 'text-yellow-600 bg-yellow-50 border border-yellow-200';
    if (value > 0) return 'text-orange-600 bg-orange-50 border border-orange-200';
    return 'text-red-600 bg-red-50 border border-red-200';
};

const handlePeriodChange = () => {
    router.get(route('dashboard.dre'), {
        year: selectedYear.value,
        month: selectedMonth.value,
    });
};
</script>

<template>
    <AppLayout title="DRE Mensal">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    DRE Mensal
                </h2>
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedYear"
                        @change="handlePeriodChange"
                        class="border-gray-300 rounded-md shadow-sm text-sm"
                    >
                        <option v-for="year in [selectedYear - 1, selectedYear, selectedYear + 1]" :key="year" :value="year">{{ year }}</option>
                    </select>
                    <select
                        v-model="selectedMonth"
                        @change="handlePeriodChange"
                        class="border-gray-300 rounded-md shadow-sm text-sm"
                    >
                        <option v-for="m in props.availableMonths" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500 mb-6">
                    Demonstrativo de Resultado de {{ formatMonthName(props.selectedMonth) }} de {{ props.selectedYear }}
                </p>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <tbody class="divide-y divide-gray-100">
                            <tr class="bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">Receita Bruta</td>
                                <td class="px-6 py-3 text-sm text-right font-medium text-gray-900">{{ formatCurrency(props.report?.totalRevenue || 0) }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 pl-10 text-sm text-gray-600">(-) Comissões</td>
                                <td class="px-6 py-3 text-sm text-right text-red-500">{{ formatCurrency(props.report?.totalCommission || 0) }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 pl-10 text-sm text-gray-600">(-) Despesas</td>
                                <td class="px-6 py-3 text-sm text-right text-red-500">{{ formatCurrency(props.report?.totalExpenses || 0) }}</td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td class="px-6 py-4 text-base font-bold text-gray-900">Lucro Líquido</td>
                                <td class="px-6 py-4 text-xl font-bold text-right" :class="profitClass(props.report?.netProfit || 0)">
                                    {{ formatCurrency(props.report?.netProfit || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 bg-white rounded-lg shadow p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Margem de Lucro</p>
                        <p class="text-2xl font-bold" :class="profitClass(props.report?.netProfit || 0)">
                            {{ ((props.report?.profitMarginPercentage || 0)).toFixed(1) }}%
                        </p>
                    </div>
                    <div class="px-4 py-2 rounded-full text-sm font-medium" :class="marginClass(props.report?.profitMarginPercentage || 0)">
                        {{ (props.report?.profitMarginPercentage || 0) >= 10 ? 'Margem saudável' : 'Margem baixa' }}
                    </div>
                </div>

                <div class="mt-6 bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Resumo</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500">Receita</p>
                            <p class="font-medium text-green-600">{{ formatCurrency(props.report?.totalRevenue || 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Comissões</p>
                            <p class="font-medium text-red-500">{{ formatCurrency(props.report?.totalCommission || 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Despesas</p>
                            <p class="font-medium text-red-500">{{ formatCurrency(props.report?.totalExpenses || 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Lucro</p>
                            <p class="font-medium" :class="profitClass(props.report?.netProfit || 0)">{{ formatCurrency(props.report?.netProfit || 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>