<?php

namespace App\BudgetManager\Services;

use App\BudgetManager\Domain\Entity\BudgetConfigurator;
use Illuminate\Database\Eloquent\Builder;
use App\BudgetManager\Domain\Model\Budget;
use App\BudgetTracker\Entity\Wallet;
use App\BudgetTracker\Enums\EntryType;
use App\BudgetTracker\Enums\PlanningType;
use App\BudgetTracker\Interfaces\EntryInterface;
use App\BudgetTracker\Models\Account;
use App\BudgetTracker\Models\Entry;
use App\BudgetTracker\Models\Labels;
use App\BudgetTracker\Models\SubCategory;
use App\User\Services\UserService;

class BudgetMamangerService
{

    public function save(array $data): void
    {
        $configuration = new BudgetConfigurator(
            $data['budget'],
            PlanningType::from($data['planningType'])
        );

        if (!empty($data['account'])) {
            foreach ($data['account'] as $account) {
                $configuration->setAccount(Account::find($account));
            }
        }

        if (!empty($data['type'])) {
            foreach ($data['type'] as $type) {
                $configuration->setType(EntryType::from($type));
            }
        }

        if (!empty($data['category'])) {
            foreach ($data['category'] as $category) {
                $configuration->setAccount(SubCategory::find($category));
            }
        }

        if (!empty($data['label'])) {
            foreach ($data['label'] as $label) {
                $configuration->setAccount(Labels::find($label));
            }
        }

        if (!empty($data['id'])) {
            $budget = Budget::find($data['id']);
        } else {
            $budget = new Budget();
        }

        $budget->budget = $data['budget'];
        $budget->configuration = $configuration->toJson();
        $budget->user_id = UserService::getCacheUserID();
        $budget->save();
    }

    public function retriveBudgetAmount(int $budgetId): array
    {
        $result = [];

        $budget = Budget::User()->where('id',$budgetId)->get();
        $config = json_decode($budget->configuration);
        $entries = $this->getEntires($config);
        $balance = new Wallet();
        foreach($entries as $entry) {
            $balance->deposit($entry->amount);
        }

        $result = [
            'name' => $budget->name,
            'budget' => $budget->budget,
            'type' => $config->type,
            'planning' => $config->planning,
            'amount' => $balance->getBalance()
        ];
        
        return $result;
    }

    public function retriveBudgetsAmount(): array
    {
        $result = [];

        $configurations = Budget::User()->get();
        foreach($configurations as $budget) {
            $config = json_decode($budget->configuration);
            $entries = $this->getEntires($config);
            $balance = new Wallet();
            foreach($entries as $entry) {
                $balance->deposit($entry->amount);
            }

            $result[] = [
                'name' => $budget->name,
                'budget' => $budget->budget,
                'type' => $config->type,
                'planning' => $config->planning_type,
                'amount' => $balance->getBalance()
            ];
        }
        
        return $result;

    }

    private function getEntires($config)
    {
            $entries = Entry::User();

            if(!empty($config->account)) {
                $entries->whereIn('account_id',(array) $config->account);
            }

            if(!empty($config->category)) {
                $entries->whereIn('category_id',(array) $config->category);
            }

            if(!empty($config->type)) {
                $entries->whereIn('type',(array) $config->type);
            }

            if(!empty($config->label)) {
                $tags = (array) $config->label;
                $entries->whereHas('label', function (Builder $q) use ($tags) {
                    $q->whereIn('labels.id', $tags);
                });
            }

            $entries = $entries->get();

            return $entries;
    }

}
