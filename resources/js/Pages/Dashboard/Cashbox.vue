<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    cashbox: Object,
    entries: Array,
    expenses: Array,
    categories: Array,
    appointments: Array,
});

const showOpenModal = ref(false);
const showEntryModal = ref(false);
const showExpenseModal = ref(false);
const showCloseModal = ref(false);

const openForm = ref({ date: new Date().toISOString().split('T')[0], opening_balance: 0 });
const entryForm = ref({ amount: '', payment_method: 'money', appointment_id: '', note: '' });
const expenseForm = ref({ amount: '', payment_method: 'money', expense_category_id: '', note: '' });
const closeForm = ref({ closing_balance: '' });

const isOpen = computed(() => props.cashbox && props.cashbox.status === 'open');

const totalEntries = computed(() => {
    if (!props.entries) return 0;
    return props.entries.reduce((sum, e) => sum + parseInt(e.amount || 0), 0);
});

const totalExpenses = computed(() => {
    if (!props.expenses) return 0;
    return props.expenses.reduce((sum, e) => sum + parseInt(e.amount || 0), 0);
});

const expectedBalance = computed(() => {
    if (!props.cashbox) return 0;
    return parseInt(props.cashbox.opening_balance) + totalEntries.value - totalExpenses.value;
});

const reconciliationStatus = computed(() => {
    if (!isOpen.value || !closeForm.value.closing_balance) return null;
    const closing = parseInt(closeForm.value.closing_balance) || 0;
    const expected = expectedBalance.value;
    if (closing === expected) return 'balanced';
    return 'discrepancy';
});

const reconciliationClass = computed(() => {
    if (reconciliationStatus.value === 'balanced') return 'bg-green-100 text-green-800 border border-green-300';
    if (reconciliationStatus.value === 'discrepancy') return 'bg-red-100 text-red-800 border border-red-300';
    return '';
});

const formatCurrency = (cents) => {
    if (cents === null || cents === undefined) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
};

const openingBalanceDisplay = computed(() => {
    if (!openForm.value.opening_balance) return '';
    return formatCurrency(parseInt(openForm.value.opening_balance) || 0);
});

const entryAmountDisplay = computed(() => {
    if (!entryForm.value.amount) return '';
    return formatCurrency(parseInt(entryForm.value.amount) || 0);
});

const expenseAmountDisplay = computed(() => {
    if (!expenseForm.value.amount) return '';
    return formatCurrency(parseInt(expenseForm.value.amount) || 0);
});

const paymentMethodLabel = (method) => {
    const labels = { money: 'Dinheiro', pix: 'PIX', debit: 'Débito', credit: 'Crédito', transfer: 'Transferência' };
    return labels[method] || method;
};

const handleOpen = () => {
    router.post(route('dashboard.cashbox.store'), {
        date: openForm.value.date,
        opening_balance: parseInt(openForm.value.opening_balance) || 0,
    }, {
        onSuccess: () => { showOpenModal.value = false; openForm.value.opening_balance = 0; },
    });
};

const handleEntry = () => {
    router.post(route('dashboard.cashbox.entry'), {
        cashbox_day_id: props.cashbox.id,
        amount: parseInt(entryForm.value.amount) || 0,
        payment_method: entryForm.value.payment_method,
        appointment_id: entryForm.value.appointment_id || null,
        note: entryForm.value.note,
    }, {
        onSuccess: () => {
            showEntryModal.value = false;
            entryForm.value = { amount: '', payment_method: 'money', appointment_id: '', note: '' };
        },
    });
};

const handleExpense = () => {
    router.post(route('dashboard.cashbox.expense'), {
        cashbox_day_id: props.cashbox.id,
        amount: parseInt(expenseForm.value.amount) || 0,
        payment_method: expenseForm.value.payment_method,
        expense_category_id: expenseForm.value.expense_category_id || null,
        note: expenseForm.value.note,
    }, {
        onSuccess: () => {
            showExpenseModal.value = false;
            expenseForm.value = { amount: '', payment_method: 'money', expense_category_id: '', note: '' };
        },
    });
};

const handleClose = () => {
    if (!confirm('Tem certeza que deseja fechar o caixa?')) return;
    router.post(route('dashboard.cashbox.close'), {
        cashbox_day_id: props.cashbox.id,
        closing_balance: parseInt(closeForm.value.closing_balance) || 0,
    }, {
        onSuccess: () => {
            showCloseModal.value = false;
            closeForm.value.closing_balance = '';
        },
    });
};
</script>

<template>
    <AppLayout title="Caixa">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Caixa Diário
                </h2>
                <div class="flex gap-2">
                    <button
                        v-if="!isOpen"
                        @click="showOpenModal = true"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                    >
                        Abrir Caixa
                    </button>
                    <template v-else>
                        <button
                            @click="showEntryModal = true"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700"
                        >
                            + Receita
                        </button>
                        <button
                            @click="showExpenseModal = true"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700"
                        >
                            + Despesa
                        </button>
                        <button
                            @click="showCloseModal = true"
                            class="inline-flex items-center px-4 py-2 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800"
                        >
                            Fechar Caixa
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-if="!props.cashbox" class="bg-white rounded-lg shadow p-8 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-gray-500 mb-4">Caixa ainda não aberto hoje.</p>
                    <button
                        @click="showOpenModal = true"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        Abrir Caixa
                    </button>
                </div>

                <template v-else>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500 mb-1">Saldo Abertura</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(props.cashbox.opening_balance) }}</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500 mb-1">Total Receitas</p>
                            <p class="text-xl font-bold text-green-600">{{ formatCurrency(totalEntries) }}</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500 mb-1">Total Despesas</p>
                            <p class="text-xl font-bold text-red-600">{{ formatCurrency(totalExpenses) }}</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500 mb-1">Saldo Esperado</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatCurrency(expectedBalance) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900">Receitas</h3>
                            </div>
                            <div class="p-4">
                                <div v-if="!props.entries || props.entries.length === 0" class="text-center py-8 text-gray-500">
                                    Nenhuma receita registrada
                                </div>
                                <table v-else class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 border-b">
                                            <th class="pb-2">Valor</th>
                                            <th class="pb-2">Forma</th>
                                            <th class="pb-2">Observação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="entry in props.entries" :key="entry.id" class="border-b border-gray-50">
                                            <td class="py-2 text-green-600 font-medium">{{ formatCurrency(entry.amount) }}</td>
                                            <td class="py-2 text-gray-600">{{ paymentMethodLabel(entry.payment_method) }}</td>
                                            <td class="py-2 text-gray-500 text-xs">{{ entry.note || '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900">Despesas</h3>
                            </div>
                            <div class="p-4">
                                <div v-if="!props.expenses || props.expenses.length === 0" class="text-center py-8 text-gray-500">
                                    Nenhuma despesa registrada
                                </div>
                                <table v-else class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 border-b">
                                            <th class="pb-2">Valor</th>
                                            <th class="pb-2">Categoria</th>
                                            <th class="pb-2">Observação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="expense in props.expenses" :key="expense.id" class="border-b border-gray-50">
                                            <td class="py-2 text-red-600 font-medium">{{ formatCurrency(expense.amount) }}</td>
                                            <td class="py-2 text-gray-600">{{ expense.category?.name || '—' }}</td>
                                            <td class="py-2 text-gray-500 text-xs">{{ expense.note || '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <Modal :show="showOpenModal" @close="showOpenModal = false" :max-width="'sm'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Abrir Caixa</h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="open-date" value="Data" />
                        <input type="date" id="open-date" v-model="openForm.date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                    </div>
                    <div>
                        <InputLabel for="open-balance" value="Saldo Inicial" />
                        <TextInput id="open-balance" v-model="openForm.opening_balance"
                            class="mt-1 block w-full" placeholder="0"
                            title="Digite o valor em centavos. Ex: 5000 = R$ 50,00" />
                        <p v-if="openingBalanceDisplay" class="mt-1 text-sm text-indigo-600 font-medium">
                            {{ openingBalanceDisplay }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showOpenModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button @click="handleOpen" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Abrir</button>
                </div>
            </div>
        </Modal>

        <Modal :show="showEntryModal" @close="showEntryModal = false" :max-width="'sm'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Receita</h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="entry-amount" value="Valor" />
                        <TextInput id="entry-amount" v-model="entryForm.amount" class="mt-1 block w-full" placeholder="0"
                            title="Digite o valor em centavos. Ex: 5000 = R$ 50,00" />
                        <p v-if="entryAmountDisplay" class="mt-1 text-sm text-indigo-600 font-medium">
                            {{ entryAmountDisplay }}
                        </p>
                    </div>
                    <div>
                        <InputLabel for="entry-method" value="Forma de Pagamento" />
                        <select id="entry-method" v-model="entryForm.payment_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="money">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="debit">Débito</option>
                            <option value="credit">Crédito</option>
                            <option value="transfer">Transferência</option>
                        </select>
                    </div>
                    <div v-if="props.appointments && props.appointments.length">
                        <InputLabel for="entry-appointment" value="Agendamento (opcional)" />
                        <select id="entry-appointment" v-model="entryForm.appointment_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Nenhum</option>
                            <option v-for="appt in props.appointments" :key="appt.id" :value="appt.id">
                                {{ appt.client?.name }} — {{ appt.service?.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="entry-note" value="Observação" />
                        <TextInput id="entry-note" v-model="entryForm.note" class="mt-1 block w-full" placeholder="Observação..." />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showEntryModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button @click="handleEntry" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Registrar</button>
                </div>
            </div>
        </Modal>

        <Modal :show="showExpenseModal" @close="showExpenseModal = false" :max-width="'sm'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Despesa</h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="expense-amount" value="Valor" />
                        <TextInput id="expense-amount" v-model="expenseForm.amount" class="mt-1 block w-full" placeholder="0"
                            title="Digite o valor em centavos. Ex: 1000 = R$ 10,00" />
                        <p v-if="expenseAmountDisplay" class="mt-1 text-sm text-red-600 font-medium">
                            {{ expenseAmountDisplay }}
                        </p>
                    </div>
                    <div>
                        <InputLabel for="expense-method" value="Forma de Pagamento" />
                        <select id="expense-method" v-model="expenseForm.payment_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="money">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="debit">Débito</option>
                            <option value="credit">Crédito</option>
                            <option value="transfer">Transferência</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="expense-category" value="Categoria" />
                        <select id="expense-category" v-model="expenseForm.expense_category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Selecione</option>
                            <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="expense-note" value="Observação" />
                        <TextInput id="expense-note" v-model="expenseForm.note" class="mt-1 block w-full" placeholder="Observação..." />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showExpenseModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button @click="handleExpense" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Registrar</button>
                </div>
            </div>
        </Modal>

        <Modal :show="showCloseModal" @close="showCloseModal = false" :max-width="'sm'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Fechar Caixa</h3>
                <div class="mb-4 p-3 rounded-md text-sm" :class="reconciliationClass">
                    <p v-if="reconciliationStatus === 'balanced'" class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Conciliação OK — valores batem
                    </p>
                    <p v-else-if="reconciliationStatus === 'discrepancy'" class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Diferença de {{ formatCurrency(Math.abs(parseInt(closeForm.closing_balance) - expectedBalance)) }}
                    </p>
                    <p v-else>Digite o saldo final para verificarconciliação.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="close-balance" value="Saldo Final (em centavos)" />
                        <TextInput id="close-balance" v-model="closeForm.closing_balance" class="mt-1 block w-full" placeholder="17000" />
                    </div>
                    <div class="text-sm text-gray-600">
                        <p>Saldo esperado: <strong>{{ formatCurrency(expectedBalance) }}</strong></p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showCloseModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button @click="handleClose" class="px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-800">Fechar Caixa</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>