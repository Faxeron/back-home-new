<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\System\Models\DevControl */
class DevControlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'er_status' => $this->er_status,
            'model_status' => $this->model_status,
            'list_api_status' => $this->list_api_status,
            'crud_api_status' => $this->crud_api_status,
            'filters_status' => $this->filters_status,
            'list_ui_status' => $this->list_ui_status,
            'form_ui_status' => $this->form_ui_status,
            'tests_status' => $this->tests_status,
            'docs_status' => $this->docs_status,
            'deploy_status' => $this->deploy_status,
            'sort_index' => $this->sort_index,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
