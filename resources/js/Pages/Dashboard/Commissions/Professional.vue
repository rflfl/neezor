<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    professional: Object,
    commissionRun: Object,
    appointments: Array,
    totalGross: Number,
    totalCommission: Number,
    period: String,
    periodStart: String,
    periodEnd: String,
});

const selectedPeriod = ref(props.period || 'monthly');

const formatCurrency = (cents) => {
    if (cents === null || cents === undefined) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
};

const formatPeriod = (start, end) => {
    if (!start || !end) return '';
    const s = new Date(start);
    const e = new Date(end);
    return `${s.toLocaleDateString('pt-BR')} — ${e.toLocaleDateString('pt-BR')}`;
};

const handlePeriodChange = () => {
    router.get(route('dashboard.commissions.professional', props.professional.id), { period: selectedPeriod.value });
};
</script>

<template>
    <AppLayout title="Comissão por Profissional">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ props.professional?.name }}
                    </h2>
                    <p class="text-sm text-gray-500">{{ formatPeriod(props.periodStart, props.periodEnd) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedPeriod"
                        @change="handlePeriodChange"
                        class="border-gray-300 rounded-md shadow-sm text-sm"
                    >
                        <option value="monthly">Mensal</option>
                        <option value="weekly">Semanal</option>
                    </select>
                    <Link
                        :href="route('dashboard.commissions.index')"
                        class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800"
                    >
                        ← Voltar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 mb-1">Total Bruto</p>
                        <p class="text-xl font-bold text-gray-900">{{ formatCurrency(props.totalGross) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 mb-1">Comissão Total</p>
                        <p class="text-xl font-bold text-indigo-600">{{ formatCurrency(props.totalCommission) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-500 mb-1">Pendente</p>
                        <p class="text-xl font-bold text-gray-900">{{ formatCurrency(props.commissionRun?.pending_amount) }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Atendimentos no Período</h3>
                    </div>
                    <div class="p-4">
                        <div v-if="!props.appointments || props.appointments.length === 0" class="text-center py-8 text-gray-500">
                            Nenhum atendimento concluído neste período.
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="pb-2">Cliente</th>
                                    <th class="pb-2">Serviço</th>
                                    <th class="pb-2">Data</th>
                                    <th class="pb-2 text-right">Valor</th>
                                    <th class="pb-2 text-right">Comissão</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="appt in props.appointments" :key="appt.id" class="border-b border-gray-50">
                                    <td class="py-2 text-gray-900">{{ appt.client?.name || '—' }}</td>
                                    <td class="py-2 text-gray-600">{{ appt.service?.name || '—' }}</td>
                                    <td class="py-2 text-gray-600">{{ new Date(appt.start_at).toLocaleDateString('pt-BR') }}</td>
                                    <td class="py-2 text-right text-gray-900 font-medium">{{ formatCurrency(appt.price) }}</td>
                                    <td class="py-2 text-right text-indigo-600">{{ formatCurrency(appt.commission) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="props.commissionRun?.payments && props.commissionRun.payments.length > 0" class="bg-white rounded-lg shadow mt-6">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Histórico de Pagamentos</h3>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="pb-2">Data</th>
                                    <th class="pb-2">Valor</th>
                                    <th class="pb-2">Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in props.commissionRun.payments" :key="payment.id" class="border-b border-gray-50">
                                    <td class="py-2 text-gray-900">{{ new Date(payment.paid_at).toLocaleDateString('pt-BR') }}</td>
                                    <td class="py-2 text-green-600 font-medium">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="py-2 text-gray-500 text-xs">{{ payment.note || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>