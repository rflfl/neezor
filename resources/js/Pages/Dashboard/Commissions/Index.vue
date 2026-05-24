<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    commissionRuns: Array,
    professionals: Array,
    period: String,
    periodStart: String,
    periodEnd: String,
});

const selectedPeriod = ref(props.period || 'monthly');
const showPayModal = ref(false);
const payForm = ref({ commission_run_id: '', amount: '', paid_at: new Date().toISOString().split('T')[0], note: '' });

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

const statusLabel = (status) => {
    const labels = { calculated: 'Pendente', paid: 'Pago' };
    return labels[status] || status;
};

const statusClass = (status) => {
    if (status === 'paid') return 'bg-green-100 text-green-800';
    if (status === 'calculated') return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
};

const handlePeriodChange = () => {
    router.get(route('dashboard.commissions.index'), { period: selectedPeriod.value });
};

const openPayModal = (run) => {
    payForm.value.commission_run_id = run.id;
    payForm.value.amount = run.pending_amount || '';
    payForm.value.paid_at = new Date().toISOString().split('T')[0];
    payForm.value.note = '';
    showPayModal.value = true;
};

const handlePay = () => {
    router.post(route('dashboard.commissions.pay'), {
        commission_run_id: payForm.value.commission_run_id,
        amount: parseInt(payForm.value.amount) || 0,
        paid_at: payForm.value.paid_at,
        note: payForm.value.note,
    }, {
        onSuccess: () => { showPayModal.value = false; },
    });
};
</script>

<template>
    <AppLayout title="Comissões">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Comissões
                </h2>
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedPeriod"
                        @change="handlePeriodChange"
                        class="border-gray-300 rounded-md shadow-sm text-sm"
                    >
                        <option value="monthly">Mensal</option>
                        <option value="weekly">Semanal</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500 mb-4">{{ formatPeriod(props.periodStart, props.periodEnd) }}</p>

                <div v-if="!props.commissionRuns || props.commissionRuns.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
                    <p class="text-gray-500">Nenhuma comissão calculada para este período.</p>
                </div>

                <div v-else class="space-y-4">
                    <div v-for="run in props.commissionRuns" :key="run.id" class="bg-white rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ run.professional?.name || 'Profissional' }}</h3>
                                <span class="text-xs px-2 py-0.5 rounded-full mt-1" :class="statusClass(run.status)">
                                    {{ statusLabel(run.status) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-right">
                                <div>
                                    <p class="text-xs text-gray-500">Total Bruto</p>
                                    <p class="font-medium text-gray-900">{{ formatCurrency(run.total_gross) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Comissão</p>
                                    <p class="font-medium text-indigo-700">{{ formatCurrency(run.total_commission) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Pendente</p>
                                    <p class="font-medium text-gray-900">{{ formatCurrency(run.pending_amount) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 py-3 flex items-center justify-between">
                            <Link
                                :href="route('dashboard.commissions.professional', run.professional_id)"
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Ver detalhes →
                            </Link>
                            <button
                                v-if="run.status !== 'paid' && run.pending_amount > 0"
                                @click="openPayModal(run)"
                                class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700"
                            >
                                Marcar como pago
                            </button>
                        </div>

                        <div v-if="run.payments && run.payments.length > 0" class="px-4 pb-3">
                            <p class="text-xs text-gray-500 mb-2">Pagamentos realizados:</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="payment in run.payments"
                                    :key="payment.id"
                                    class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded border border-green-200"
                                >
                                    {{ formatCurrency(payment.amount) }} em {{ new Date(payment.paid_at).toLocaleDateString('pt-BR') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showPayModal" @close="showPayModal = false" :max-width="'sm'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Pagamento</h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="pay-amount" value="Valor (em centavos)" />
                        <TextInput id="pay-amount" v-model="payForm.amount" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="pay-date" value="Data do Pagamento" />
                        <input type="date" id="pay-date" v-model="payForm.paid_at" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                    </div>
                    <div>
                        <InputLabel for="pay-note" value="Observação" />
                        <TextInput id="pay-note" v-model="payForm.note" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showPayModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button @click="handlePay" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Confirmar</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>