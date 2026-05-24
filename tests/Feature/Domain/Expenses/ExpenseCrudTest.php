<?php

namespace Tests\Feature\Domain\Expenses;

use App\Domain\Cashbox\Models\ExpenseCategory;
use App\Domain\Expenses\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->category = ExpenseCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        TenantContext::setCurrent($this->tenant->id);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_can_list_expenses(): void
    {
        Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 5000,
            'expense_category_id' => $this->category->id,
            'is_recurring' => false,
            'description' => 'Produto cabelo',
            'due_date' => '2026-05-15',
        ]);

        $response = $this->actingAs($this->user)->getJson('/dashboard/expenses');

        $response->assertOk()
            ->assertJsonCount(1, 'expenses');
    }

    public function test_can_create_expense(): void
    {
        $response = $this->actingAs($this->user)->postJson('/dashboard/expenses', [
            'amount' => 3000,
            'expense_category_id' => $this->category->id,
            'is_recurring' => true,
            'description' => 'Aluguel',
            'due_date' => '2026-05-20',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $this->tenant->id,
            'amount' => 3000,
            'description' => 'Aluguel',
            'is_recurring' => true,
        ]);
    }

    public function test_can_show_expense(): void
    {
        $expense = Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 7000,
            'expense_category_id' => $this->category->id,
            'is_recurring' => false,
            'description' => 'Luz',
            'due_date' => '2026-05-25',
        ]);

        $response = $this->actingAs($this->user)->getJson("/dashboard/expenses/{$expense->id}");

        $response->assertOk()
            ->assertJsonPath('expense.amount', 7000)
            ->assertJsonPath('expense.description', 'Luz');
    }

    public function test_can_update_expense(): void
    {
        $expense = Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 2000,
            'expense_category_id' => $this->category->id,
            'is_recurring' => false,
            'description' => 'Internet',
            'due_date' => '2026-05-10',
        ]);

        $response = $this->actingAs($this->user)->putJson("/dashboard/expenses/{$expense->id}", [
            'amount' => 2500,
            'description' => 'Internet fibra',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 2500,
            'description' => 'Internet fibra',
        ]);
    }

    public function test_can_delete_expense(): void
    {
        $expense = Expense::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'amount' => 1000,
            'expense_category_id' => $this->category->id,
            'is_recurring' => false,
            'due_date' => '2026-05-30',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/dashboard/expenses/{$expense->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_tenant_isolation_on_expenses(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherExpense = Expense::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'amount' => 99999,
            'expense_category_id' => null,
            'is_recurring' => false,
            'due_date' => '2026-05-01',
        ]);

        $response = $this->actingAs($this->user)->getJson('/dashboard/expenses');

        $response->assertOk();
        $expenseIds = collect($response->json('expenses'))->pluck('id')->toArray();
        $this->assertNotContains($otherExpense->id, $expenseIds);
    }

    public function test_validation_requires_amount_and_due_date(): void
    {
        $response = $this->actingAs($this->user)->postJson('/dashboard/expenses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'due_date']);
    }
}