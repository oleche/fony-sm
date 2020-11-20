<?php

namespace Geekcow\FonySM;

class StateMachine {
  private $transitions = [
    'create' => array ("from"=>[''],"to"=>'created'),
    'start' => array ("from"=>['created'],"to"=>'in progress'),
    'accept' => array ("from"=>['in progress'],"to"=>'accepted'),
    'complete' => array ("from"=>['accepted'],"to"=>'completed'),
    'reject' => array ("from"=>['accepted', 'in progress'],"to"=>'rejected')
  ]

  private $err;
  private $this->model;
  private $state_column;

  public function __construct($transitions){
    $this->err = "";
    $this->state_column = "state";
    $this->transitions = [];
    foreach ($transitions as $transition => $value) {
      if (is_array($value) && isset($value["from"]) && isset($value["to"])){
        if (!$this->addTransition($transition, $value["from"], $value["to"])){
          throw new \Exception("Could not add one transition");
        }
      }else{
        throw new \Exception("Unexpected transition");
      }
    }
  }

  public function setModel($model){
    $this->model = $model;
  }

  public function setStateColumn($column){
    $this->state_column = $column;
  }

  protected function addTransition($name, $from, $to){
    if (is_array($from) && !is_array($to)){
      $this->transition[$name] = array("from"=>$from,"to"=>$to);
    }else{
      return false;
    }
  }

  public function doTransition($event) {
    $current_state = $this->model->columns[$this->state_column];
    if (isset($this->$transitions[$event])){
      if (in_array($current_state, $transitions[$event]["from"])){
        $theMap = $this->model->get_mapping();
        foreach ($theMap as $k => $map) {
          $currentValue = $this->model->columns[$k];
          if (isset($map['foreign'])){
            $currentValue = $this->model->columns[$k][$map['foreign'][0]];
          }
          $this->model->columns[$k] = $currentValue;
        }
        if (isset($this->model->columns['updated_at'])){
          $this->model->columns['updated_at'] = date("Y-m-d H:i:s");
        }
        //update the state
        $this->model->columns[$this->state_column] = $transitions[$event]["to"];
        if (!$this->model->update()){
          $this->err = 'Cannot update';
          return false;
        }
      }else{
        $this->err = "State not in transition";
        return false;
      }
    }else{
      $this->err = "No event found";
      return false;
    }
    return true;
  }

  public function setInitialState() {

  }
}

?>
