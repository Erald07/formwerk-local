<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Record;
use App\Models\Company;
use App\Models\RecordFileDeletionCronJob;
use Carbon\Carbon;

class DeleteRecordFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'record:remove-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removed record related files depending on the interval set for the company';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $fileTypes = ['xml_file_name', 'csv_file_name', 'custom_report_file_name'];

    private function deleteFiles($record)
    {
        foreach ($this->fileTypes as $fileType) {
            if (isset($record[$fileType]) && !empty($record[$fileType])) {
                if (Storage::disk("private")->exists($record[$fileType])) {
                    Storage::disk("private")->delete($record[$fileType]);
                }
                $record[$fileType] = null;
            }
        }
        return $record;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companies = Company::withTrashed()
            ->select('companies.id', 'companies.company_name', 'settings.value')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('settings')
                    ->whereColumn('settings.company_id', 'companies.id')
                    ->where('settings.value', '1');
            })
            ->join('settings', function ($join) {
                $join->on('companies.id', '=', 'settings.company_id')
                    ->where('settings.name', 'automatic-file-delete-interval');
            })->get();

        foreach ($companies as $company) {
            if (isset($company->value) && !empty($company->value)) {
                $days = (int) $company->value;
                $records = Record::withoutGlobalScope('deleted')->select('id', 'xml_file_name', 'csv_file_name', 'custom_report_file_name')
                    ->where('company_id', $company["id"])
                    ->whereDate('created_at', '<=', Carbon::now()->subDays($days))
                    ->get();
                if (count($records) > 0) {
                    echo "Deleting " . count($records) . " records from company " . $company['company_name'];
                    foreach ($records as $record) {
                        $record = $this->deleteFiles($record);
                        $record->deleted = 1;
                        // Saving record here
                        $record->save();
                        // deleting record
                        $record->delete();
                    }
                    $cron = new RecordFileDeletionCronJob;
                    $cron->company_id = $company["id"];
                    $cron->deleted_records = count($records);
                    $cron->save();
                }
            }
        }
        // $this->info(\Illuminate\Support\Str::replaceArray('?', $records->getBindings(), $records->toSql()));
    }
}
