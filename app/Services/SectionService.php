<?php

namespace App\Services;

use App\Models\Section;

// B12: ports the Postgres trigger enforce_section_term_uniqueness.
// A section name must be unique per academic term per educator — this spans the
// section<->term M:N link, so it is not a single DB unique constraint and is
// enforced here in application code instead.
class SectionService
{
    /**
     * True if the educator already has a section with this name linked to any of
     * the given terms. Pass $ignoreSectionId when updating to exclude self.
     *
     * @param  array<int>  $academicTermIds
     */
    public function nameTakenForTerms(
        int $educatorId,
        string $sectionName,
        array $academicTermIds,
        ?int $ignoreSectionId = null,
    ): bool {
        return Section::query()
            ->where('educator_id', $educatorId)
            ->where('section_name', $sectionName)
            ->when($ignoreSectionId, fn ($q) => $q->whereKeyNot($ignoreSectionId))
            ->whereHas('terms', fn ($q) => $q->whereIn('tbl_academic_term.id', $academicTermIds))
            ->exists();
    }
}
