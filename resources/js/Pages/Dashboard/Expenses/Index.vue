<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref } from 'vue';

const props = defineProps({
    expenses: Array,
    categories: Array,
});

const showModal = ref(false);
const showExpenseModal = ref(false);
const editingExpense = ref(null);
const expenseForm = ref({ amount: '', expense_category_id: '', is_recurring: false, description: '', due_date: '' });

const formatCurrency = (cents) => {
    if (cents === null || cents === undefined) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
};

const openExpenseModal = (expense = null) => {
    if (expense) {
        editingExpense.value = expense;
        expenseForm.value = { ...expense };
    } else {
        editingExpense.value = null;
        expenseForm.value = { amount: '', expense_category_id: '', is_recurring: false, description: '', due_date: '' };
    }
    showExpenseModal.value = true;
};

const handleSave = () => {
    const method = editingExpense.value ? 'put' : 'post';
    const url = editingExpense.value
        ? route('dashboard.expenses.update', editingExpense.value.id)
        : route('dashboard.expenses.store');
    router[method](url, expenseForm.value, {
        onSuccess: () => { showExpenseModal.value = false; },
    });
};

const handleDelete = (id) => {
    if (confirm('Tem certeza que deseja excluir esta despesa?')) {
        router.delete(route('dashboard.expenses.destroy', id));
    }
};
</script>

<template>
    <AppLayout title="Despesas">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Despesas
                </h2>
                <button
                    @click="openExpenseModal()"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                >
                    + Nova Despesa
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow">
                    <div v-if="!props.expenses || props.expenses.length === 0" class="p-8 text-center text-gray-500">
                        Nenhuma despesa registrada.
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b bg-gray-50">
                                <th class="px-4 py-3">Descrição</th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Vencimento</th>
                                <th class="px-4 py-3 text-right">Valor</th>
                                <th class="px-4 py-3 text-center">Recorrente</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="expense in props.expenses" :key="expense.id" class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-900">{{ expense.description || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ expense.category?.name || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ new Date(expense.due_date).toLocaleDateString('pt-BR') }}</td>
                                <td class="px-4 py-3 text-right text-red-600 font-medium">{{ formatCurrency(expense.amount) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="expense.is_recurring" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">Sim</span>
                                    <span v-else class="text-xs text-gray-400">Não</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="openExpenseModal(expense)" class="text-indigo-600 hover:text-indigo-800 mr-2">Editar</button>
                                    <button @click="handleDelete(expense.id)" class="text-red-600 hover:text-red-800">Excluir</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showExpenseModal" @close="showExpenseModal = false" :max-width="'sm'">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingExpense ? 'Editar Despesa' : 'Nova Despesa' }}
                </h3>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="expense-desc" value="Descrição" />
                        <TextInput id="expense-desc" v-model="expenseForm.description" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="expense-amount" value="Valor (em centavos)" />
                        <TextInput id="expense-amount" v-model="expenseForm.amount" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="expense-cat" value="Categoria" />
                        <select id="expense-cat" v-model="expenseForm.expense_category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Selecione</option>
                            <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="expense-date" value="Data de Vencimento" />
                        <input type="date" id="expense-date" v-model="expenseForm.due_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="expense-recurring" v-model="expenseForm.is_recurring" class="rounded border-gray-300" />
                        <label for="expense-recurring" class="text-sm text-gray-700">Despesa recorrente</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showExpenseModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancelar</button>
                    <button @click="handleSave" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Salvar</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>