<?php

namespace App\Http\Requests;

// G5: edit assessment. Updates the current row (its own subject); any additional selected
// subjects become NEW assessments. Same rules as create (no uniqueness constraint).
class UpdateAssessmentRequest extends StoreAssessmentRequest {}
