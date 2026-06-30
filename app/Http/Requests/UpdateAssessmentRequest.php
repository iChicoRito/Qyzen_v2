<?php

namespace App\Http\Requests;

// G5: edit assessment. Same rules; uniqueness excludes self.
class UpdateAssessmentRequest extends StoreAssessmentRequest
{
    protected function ignoreId(): ?int
    {
        return $this->route('assessment')->id;
    }
}
