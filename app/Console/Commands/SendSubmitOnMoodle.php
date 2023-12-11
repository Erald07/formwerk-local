<?php

namespace App\Console\Commands;

use App\Models\Record;
use App\Models\Setting;
use Illuminate\Console\Command;

use GuzzleHttp;
class SendSubmitOnMoodle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moodle:submit {record}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resend submit one more time to moodle for given record';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $record = Record::firstWhere("id", $this->argument('record'));
        if(!$record) {
            $this->error('Record not found');
            return Command::FAILURE;
        }
        $predefinedValues = json_decode($record->predefined_values, true);
        if (is_array($predefinedValues) && isset($predefinedValues['user_id']) && isset($predefinedValues['course_db_id'])) {
            $record->moodle_user_id = (int)$predefinedValues['user_id'];
            $record->moodle_course_id = (int)$predefinedValues['course_db_id'];
            $moodleUrl = Setting::where('company_id', $record->company_id)->where('name', 'moodle-base-url')->first();
            $apiToken = Setting::where('company_id', $record->company_id)->where('name', 'predefined-values-secret')->first();
            if (isset($moodleUrl) && !empty($moodleUrl->value) && isset($apiToken) && !empty($apiToken->value)) {
                $moodle_url = rtrim($moodleUrl->value, '/') . '/local/formwerk/form_submitted.php';
                // post to moodle
                $client = new GuzzleHttp\Client();
                try {
                    $response = $client->post($moodle_url, [
                        'form_params' => [
                            'user_id' => $record->moodle_user_id,
                            'form_id' => $record->form_id,
                            'course_id' => (int)$predefinedValues['course_db_id'],
                        ],
                        'headers' => ["X-Formwerk-Api-Token" => $apiToken->value]
                    ]);
                    $res = json_decode($response->getBody());
                    if (isset($res->success) && $res->success) {
                        $record->sent_to_moodle = 1;
                    }
                    $this->info('Submit successfylly saved');
                } catch (\Exception $ex) {
                    $this->error($ex->getMessage());
                }
            } else {
                $this->error('Settings are not configured');
            }
        } else {
            $this->error('Predefined values are not set');
        }
        return Command::SUCCESS;
    }
}
