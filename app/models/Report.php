<?php

use Jenssegers\Mongodb\Model as Eloquent;

class Report extends Eloquent {

  /**
   * Our MongoDB collection used by the model.
   *
   * @var string
   */
  protected $collection = 'reports';
  public static $rules = [];
  protected $fillable = ['name', 'description', 'query', 'lrs', 'since', 'until'];

  public function getFilterAttribute() {
    $reportArr = $this->toArray();
    $filter = [];

    if (isset($reportArr['query'])) $filter['filter'] = json_encode($reportArr['query']);
    if (isset($reportArr['since'])) $filter['since'] = $reportArr['since'];
    if (isset($reportArr['until'])) $filter['until'] = $reportArr['until'];

    return $filter;
  }

  public function getMatchAttribute() {
    $reportArr = $this->toArray();
    $match = [];
    $query = isset($reportArr['query']) ? (array) $reportArr['query'] : null;

    if (is_array($query) && count($query) > 0 && !isset($query[0])) {
      foreach ($query as $key => $value) {
        $match[$key] = ['$in' => $value];
      }
    }

    $since = isset($reportArr['since']) ? $reportArr['since'] : null;
    $until = isset($reportArr['until']) ? $reportArr['until'] : null;

    if ($since || $until) {
      $match['statement.timestamp'] = [];
    }
    if ($since) {
      $match['statement.timestamp']['$gte'] = $since;
    }
    if ($until) {
      $match['statement.timestamp']['$lte'] = $until;
    }

    return $match;
  }

  public function getWhereAttribute() {
    $reportArr = $this->toArray();
    $wheres = [];
    $query = isset($reportArr['query']) ? (array) $reportArr['query'] : null;

    if (is_array($query) && count($query) > 0 && !isset($query[0])) {
      $wheres = array_map(function ($key) use ($query) {
        return [$key, 'in', $query[$key]];
      }, array_keys($query));
    }

    $since = isset($reportArr['since']) ? $reportArr['since'] : null;
    $until = isset($reportArr['until']) ? $reportArr['until'] : null;

    if ($since && $until) {
      $wheres[] = ['statement.timestamp', 'between', $since, $until];
    } else if ($since) {
      $wheres[] = ['statement.timestamp', '>=', $since];
    } else if ($until) {
      $wheres[] = ['statement.timestamp', '<=', $until];
    }

    return $wheres;
  }

  public function toArray() {
    return (array) \app\locker\helpers\Helpers::replaceHtmlEntity(parent::toArray());
  }

}