<?php

declare(strict_types=1);

namespace App\Bot\Fsm;

/** состояние после кнопки «сообщить о проблеме», черновик в bot_sessions.payload. */
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
