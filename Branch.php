<?php
namespace GitView;

use VcsCommon\BaseBranch;

/**
 * Represents GIT branch model
 */
class Branch extends BaseBranch
{
    /**
     * Returns head commit instance
     *
     * @return Commit
     * @throws CommonException
     */
    public function getHeadCommit()
    {
        return $this->repository->getCommit($this->head);
    }
}