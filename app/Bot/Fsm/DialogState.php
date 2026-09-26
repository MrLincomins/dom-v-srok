<?php

declare(strict_types=1);

namespace App\Bot\Fsm;

enum DialogState: string
{
    case Idle = 'idle';
    case ChooseHouse = 'choose_house';
    case AskEmergency = 'ask_emergency';
    case AskCategory = 'ask_category';
    case AskSubcategory = 'ask_subcategory';
    case AskDetails = 'ask_details';
    case AskAddress = 'ask_address';
    case Preview = 'preview';
}
