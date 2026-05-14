<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class BulkContentForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string')]
    public string $body = '';
}
