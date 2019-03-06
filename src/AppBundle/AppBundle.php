<?php
.....
class ConfirmEmailController extends Controller {
  ....   
  public function confirmEmailAction($id, $hash) {
    $form = $this->applicationRepository->findByHash($id, $hash);
    
    // Current place - start
    $this->workflow->apply($form, 'confirm_email');
    $this->workflow->apply($form, 'validate_application');
    
    try {
      $this->workflow->apply($form, 'confirm_email');
    } catch(LogicException $e) {
      // Cannot apply transition, probably already confirmed
    }
    ....
  }
  
}
