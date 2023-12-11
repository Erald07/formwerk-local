<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use App\Service\LeformService;
use Illuminate\Http\Request;
use App\Models\Style;

class ThemeController extends Controller
{
    public function renameTheme(Request $request, LeformService $leform)
    {
        $callback = '';
        if ($request->has('callback')) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
        }

        if (
            $request->has('id')
            && substr($request->input('id'), 0, strlen('native-')) == 'native-'
        ) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Can not rename native theme.'),
            ];
            if (!empty($callback)) {
                return $callback.'('.json_encode($return_data).')';
            } else {
                return json_encode($return_data);
            }
        }

        $style_id = null;
        if ($request->has('style-id')) {
            $style_id = intval($request->input('style-id'));

            #$style_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_styles WHERE deleted = '0' AND id = '".esc_sql($style_id)."'", ARRAY_A);
            $style_details = Style::where('deleted', 0)
                ->where('id', $style_id)
                ->first();

            if (empty($style_details)) {
                $style_id = null;
            }
        }

        if (empty($style_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested theme not found.'),
            ];
            if (!empty($callback)) {
                return $callback.'('.json_encode($return_data).')';
            } else {
               return json_encode($return_data);
            }
        }

        $style_name = null;
        if (
            $request->has('name')
            && !empty($request->input('name'))
        ) {
            $style_name = trim(base64_decode($request->input('name')));
        }

        if (empty($style_name)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Theme name can not be empty.'),
            ];
            if (!empty($callback)) {
                return $callback.'('.json_encode($return_data).')';
            } else {
               return json_encode($return_data);
            }
        }

        #$wpdb->query("UPDATE ".$wpdb->prefix."leform_styles SET name = '".esc_sql($style_name)."' WHERE deleted = '0' AND id = '".esc_sql($style_id)."'");
        Style::where('deleted', 0)
            ->where('id', $style_id)
            ->update([ 'name' => $style_name ]);

        $styles = $leform->get_styles();
        $return_data = [
            'status' => 'OK',
            'name' => $style_name,
            'message' => __('Theme successfully renamed.'),
            'styles' => $styles
        ];

        if (!empty($callback)) {
            return $callback.'('.json_encode($return_data).')';
        } else {
           return json_encode($return_data);
        }
    }

    public function deleteTheme(Request $request)
    {
        if ($request->has('callback')) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
        }

        if (
            $request->has('id')
            && substr($request->input('id'), 0, strlen('native-')) == 'native-'
        ) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Can not delete native theme.'),
            ];
            if (!empty($callback)) {
                return $callback.'('.json_encode($return_data).')';
            } else {
               return json_encode($return_data);
            }
        }

        $style_id = null;
        if ($request->has('style-id')) {
            $style_id = intval($request->input('style-id'));

            #$style_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_styles WHERE deleted = '0' AND id = '".esc_sql($style_id)."'", ARRAY_A);
            $style_details = Style::where('deleted', 0)
                ->where('id', $style_id)
                ->first();

            if (empty($style_details)) {
                $style_id = null;
            }
        }

        if (empty($style_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested theme not found.'),
            ];
            if (!empty($callback)) {
                return $callback.'('.json_encode($return_data).')';
            } else {
                return json_encode($return_data);
            }
        }

        #$wpdb->query("UPDATE ".$wpdb->prefix."leform_styles SET deleted = '1' WHERE deleted = '0' AND id = '".esc_sql($style_id)."'");
        Style::where('deleted', 0)
            ->where('id', $style_id)
            ->update([ 'deleted' => 1 ]);

        #$styles = $wpdb->get_results("SELECT id, name, type FROM ".$wpdb->prefix."leform_styles WHERE deleted = '0' ORDER BY type DESC, name ASC", ARRAY_A);
        $styles = Style::where('deleted', 0)
            ->orderBy('type', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $return_data = [
            'status' => 'OK',
            'message' => __('The theme successfully deleted.'),
            'styles' => $styles,
        ];

        if (!empty($callback)) {
            return $callback.'('.json_encode($return_data).')';
        } else {
            return json_encode($return_data);
        }
    }

    public function exportStyle(Request $request)
    {
        $style_id = intval($request->input("id"));
        $style = Style::where('deleted', 0)
            ->where('id', $style_id)
            ->first();

        if (!$style) {
            return response(__('Style not found'), 404);
        }

        $fileContents = json_encode([
            'name' => $style['name'],
            'options' => $style['options'],
        ]);

        return response()->streamDownload(function () use ($fileContents) {
            echo $fileContents;
        }, urlencode($style['name']) . '.json');
    }

    public function importStyle(Request $request)
    {
        $user = $request->user();

        $file = json_decode(File::get($request->file('leform-file')));
        $name = $file->name;
        $options = $file->options;

        $style = Style::create([
            'name' => $name,
            'options' => $options,
            'type' => 0, #LEFORM_STYLE_TYPE_USER
            'deleted' => 0,
            'user_id' => $user->id,
        ]);

        return [
            'status' => "OK",
            'id' => $style->id,
            'name' => $style->name,
            'type' => $style->type,
            'message' => $style->message,
        ];
    }

}
