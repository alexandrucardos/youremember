<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-mail',
    description: 'Send a test email'
)]
class SendTestMailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('script@youremember.org')
            ->to('alec.cardos@gmail.com')
            ->subject('Test email from Symfony command')
            ->text('This email was sent using a Symfony console command.')
            ->html('<p>This email was sent using a <strong>Symfony console command</strong>.</p>');

        $this->mailer->send($email);

        $output->writeln('<info>Email sent successfully.</info>');

        return Command::SUCCESS;
    }
}
