<?php

namespace Camera;

class StreamProcessCollection
{
    /**
     * @var StreamProcess[]
     */
    private array $processes = [];

    public function add(StreamProcess $streamProcess): void
    {
        $this->processes[$streamProcess->getId()] = $streamProcess;
    }

    public function isRunning(string $id): bool
    {
        return isset($this->processes[$id]);
    }


    public function getRunningStreamIds(): array
    {
        return array_keys($this->processes);
    }

    public function stop(string $id): void
    {
        $this->processes[$id]->stop();
        unset($this->processes[$id]);
    }
    /**
     * @param string[] $ids
     */
    public function stopByIds(array $ids, \Closure $callback): void
    {
        foreach ($ids as $id) {
            if (!isset($this->processes[$id])) {
                continue;
            }
            if ($callback) {
                $callback($id);
            }
            $this->processes[$id]->stop();
            unset($this->processes[$id]);
        }
    }

    public function stopAll(): void
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }
        $this->processes = [];
    }

    /**
     * @return StreamProcess[]
     */
    public function getAll(): array
    {
        return $this->processes;
    }

}
