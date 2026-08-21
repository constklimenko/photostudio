<?php

namespace App\Actions\Inquiry;

use App\Models\Inquiry;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProjectFromInquiry
{
    public function execute(Inquiry $inquiry, array $data): Project
    {
        return DB::transaction(function () use ($inquiry, $data) {
            $slug = Str::slug($data['title']);
            $base = $slug;
            $counter = 1;
            while (Project::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$counter++;
            }

            $project = Project::create([
                'slug' => $slug,
                'title' => $data['title'],
                'type' => $data['type'],
                'client_id' => $data['client_id'] ?? $inquiry->user_id,
                'manager_id' => $data['manager_id'] ?? null,
                'shooting_date' => $data['shooting_date'] ?? $inquiry->shooting_date,
                'status' => 'draft',
                'contact_name' => $inquiry->name,
                'contact_phone' => $inquiry->phone,
                'contact_email' => $inquiry->email,
            ]);

            $inquiry->update(['project_id' => $project->id]);

            return $project;
        });
    }
}
