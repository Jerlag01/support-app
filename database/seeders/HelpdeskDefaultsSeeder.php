<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\SlaPlan;
use App\Support\HelpdeskSettings;
use Illuminate\Database\Seeder;

/**
 * Starting configuration: SLA plans, departments and help topics. Safe to run more than once;
 * existing records (matched by name) are left alone so admin changes are never overwritten.
 */
class HelpdeskDefaultsSeeder extends Seeder
{
    public function run(HelpdeskSettings $settings): void
    {
        $standard = SlaPlan::firstOrCreate(['name' => 'Standaard (48u)'], ['grace_hours' => 48]);
        $urgent = SlaPlan::firstOrCreate(['name' => 'Dringend (4u)'], ['grace_hours' => 4]);

        $support = Department::firstOrCreate(['name' => 'Support'], ['sla_plan_id' => $standard->id]);
        $service = Department::firstOrCreate(['name' => 'Service & onderhoud'], ['sla_plan_id' => $standard->id]);
        $administration = Department::firstOrCreate(['name' => 'Administratie'], ['sla_plan_id' => $standard->id]);

        $topics = [
            ['malfunction', 'bolt', $service, $urgent, TicketPriority::High],
            ['maintenance', 'wrench', $service, null, TicketPriority::Normal],
            ['installation', 'panel', $support, null, TicketPriority::Normal],
            ['quote', 'file', $support, null, TicketPriority::Low],
            ['billing', 'receipt', $administration, null, TicketPriority::Normal],
            ['other', 'chat', $support, null, TicketPriority::Normal],
        ];

        foreach ($topics as $order => [$key, $icon, $department, $slaPlan, $priority]) {
            $name = $this->translations("support.categories.{$key}");

            if (HelpTopic::where('name->nl', $name['nl'])->exists()) {
                continue;
            }

            HelpTopic::create([
                'name' => $name,
                'description' => $this->translations("support.category_descriptions.{$key}"),
                'icon' => $icon,
                'department_id' => $department->id,
                'sla_plan_id' => $slaPlan?->id,
                'default_priority' => $priority,
                'sort_order' => $order,
            ]);
        }

        if ($settings->get('default_department_id') === null) {
            $settings->save(['default_department_id' => $support->id, 'default_sla_plan_id' => $standard->id]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function translations(string $key): array
    {
        return collect(config('app.locales'))->keys()->mapWithKeys(fn (string $locale): array => [$locale => __($key, [], $locale)])->all();
    }
}
