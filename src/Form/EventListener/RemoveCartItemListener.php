<?php

namespace App\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RemoveCartItemListener implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents() : array
    {
        return [FormEvents::POST_SUBMIT => 'postSubmit'];
    }

    /**
     * Removes Items from the Cart
     */

     public function postSubmit(FormEvent $event)
     {
        $form = $event->getForm();
        $cart = $form->getData();

        // Removes items from the cart
        foreach ($form->get('items')->all() as $child) {
            if ($child->get('remove')->isClicked()) {
                // Assuming you have a method to remove an item from your session-based cart
                // Replace this with your actual logic to remove an item from the cart array
                $this->removeItemFromCart($cart, $child->getData());
                break;
            }
        }
     }
     private function removeItemFromCart(array &$cart, $itemToRemove): void
     {
         // Trouver la clé de l'élément à supprimer dans le tableau
         $keyToRemove = null;
         foreach ($cart as $key => $item) {
             if ($item['product_id'] === $itemToRemove['product_id'] && $item['color'] === $itemToRemove['color'] && $item['size'] === $itemToRemove['size']) {
                 $keyToRemove = $key;
                 break;
             }
         }
     
         // Supprimer l'élément du tableau en utilisant la clé
         if ($keyToRemove !== null) {
             unset($cart[$keyToRemove]);
         }
     }
}