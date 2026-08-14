<?php

namespace App\Support\DynamicRecordView\Core\Content;

use App\Support\DynamicRecordView\Core\SubApplication;

/**
 * Renders the "Other Data" section's list of Sub Applications for the
 * current record. Sub Applications themselves stay defined via
 * DynamicRecordView::subApplications() — this content block just carries
 * them into a tab.
 */
class SubApplicationContent extends Content
{
    /** @var SubApplication[] */
    protected array $subApplications = [];

    /**
     * @param  SubApplication[]  $subApplications
     */
    public function subApplications(array $subApplications): static
    {
        $this->subApplications = $subApplications;

        return $this;
    }

    /**
     * @return SubApplication[]
     */
    public function getSubApplications(): array
    {
        return $this->subApplications;
    }
}
