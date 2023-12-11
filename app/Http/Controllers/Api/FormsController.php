<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FormResource;
use App\Models\Folder;
use Illuminate\Http\Request;
use App\Models\Form;
use Illuminate\Routing\Controller as BaseController;

class FormsController extends BaseController
{

  public function list(Request $request)
  {
    $company = get_company_by_token($request->header('X-Formwerk-Api-Token'));
    if ($company) {
      $forms = Form::where('company_id', $company->id)->where('options', 'like', '%"show-on-api":"on"%')->withoutGlobalScopes(['company'])->get();
      return FormResource::collection($forms);
    }
    return FormResource::collection([]);
  }

  public function formsWithFolders(Request $request)
  {
    $company = get_company_by_token($request->header('X-Formwerk-Api-Token'));
    if ($company) {
      $forms = Form::where('company_id', $company->id)->where('options', 'like', '%"show-on-api":"on"%')->withoutGlobalScopes(['company'])->get();
      $folders = Folder::where('company_id', $company->id)->get();
      $mappedFolders = [
        0 => [
          'name' => '',
          'id' => 0,
          'parent_id' => 0,
          'children' => [],
          'forms' => [],
        ]
      ];
      $childrenFolders = [];
      $formsOnChildren = [];
      $childrenFolderIds = [];
      foreach ($folders as $folder) {
        if (empty($folder->parent_id)) {
          $item = [
            'name' => $folder->name,
            'id' => $folder->id,
            'parent_id' => 0,
            'children' => [],
            'forms' => [],
          ];
          $mappedFolders[$folder->id] = $item;
        } else {
          $childrenFolders[] = $folder;
          $childrenFolderIds[] = $folder->id;
        }
      }

      foreach ($forms as $form) {
        if (empty($form->folder_id) || isset($mappedFolders[$form->folder_id]) || !in_array($form->folder_id, $childrenFolderIds)) {
          $folder_id = (empty($form->folder_id) || !isset($mappedFolders[$form->folder_id])) ? 0 : $form->folder_id;
          $mappedFolders[$folder_id]['forms'][] = [
            'id' => $form->id,
            'name' => $form->name,
            'deleted' => $form->deleted,
            'active' => $form->active,
            'link' => FormResource::getFormShortLink($form->short_link),
            'createdAt' => (string) $form->created_at,
          ];
        } else {
          $formsOnChildren[] = $form;
        }
      }

      $response = [];
      foreach ($mappedFolders as $folder_id => $item) {
        if (count($formsOnChildren) > 0) {
          $res = $this->getFolderHierarchy($folder_id, $childrenFolders, $formsOnChildren, $item);
        } else {
          $res = $item;
        }
        if (count($res['children']) > 0 || count($res['forms']) > 0) {
          $response[] = $res;
        }
      }

      return $response;
    }
    return FormResource::collection([]);
  }

  public function getForm(Request $request, $id)
  {
    $company = get_company_by_token($request->header('X-Formwerk-Api-Token'));
    if ($company) {
      $form = Form::where('company_id', $company->id)->where('id', $id)->where('options', 'like', '%"show-on-api":"on"%')->withoutGlobalScopes(['company'])->first();
      if ($form) {
        return response([
          'data' => [
            'id' => $form->id,
            'name' => $form->name,
            'deleted' => $form->deleted,
            'link' => FormResource::getFormShortLink($form->short_link),
            'createdAt' => (string) $form->created_at,
          ]
        ]);
      }
    }
    return response([
      'data' => new \stdClass()
    ]);
  }

  private function getFolderHierarchy($parent_id, $folders, $forms, $item)
  {
    foreach ($folders as $folder) {
      if ($folder->parent_id === $parent_id) {
        $child = [
          'name' => $folder->name,
          'id' => $folder->id,
          'parent_id' => 0,
          'children' => [],
          'forms' => [],
        ];
        foreach ($forms as $form) {
          if ($form->folder_id === $folder->id) {
            $child['forms'][] = [
              'id' => $form->id,
              'name' => $form->name,
              'deleted' => $form->deleted,
              'active' => $form->active,
              'link' => FormResource::getFormShortLink($form->short_link),
              'createdAt' => (string) $form->created_at,
            ];
          }
        }
        $child = $this->getFolderHierarchy($folder->id, $folders, $forms, $child);
        if (count($child['children']) > 0 || count($child['forms']) > 0) {
          $item['children'][] = $child;
        }
      }
    }

    return $item;
  }
}
