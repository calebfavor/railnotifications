<?php

namespace Railroad\Railnotifications\Notifications\Mailers;

use Illuminate\Contracts\Mail\Mailer;
use Railroad\Railnotifications\Contracts\UserProviderInterface;
use Railroad\Railnotifications\Notifications\Emails\AggregatedNotificationsEmail;
use Throwable;

class ForumPostReplyMailer implements MailerInterface
{
    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    public function __construct(
        Mailer $mailer,
        UserProviderInterface $userProvider
    ) {
        $this->mailer = $mailer;
        $this->userProvider = $userProvider;
    }

    /**
     * @param array $notifications
     * @throws Throwable
     */
    public function send(array $notifications)
    {
        $notificationsViews = [];

        foreach ($notifications as $notification) {
            $post = $notification->getData()['post'];

            $thread = $notification->getData()['thread'];

            $receivingUser = $notification->getRecipient();

            /**
             * @var $author User
             */
            $author = $this->userProvider->getRailnotificationsUserById($post['author_id']);

            $notificationsViews[$receivingUser->getEmail()][] = view(
                'railnotifications::forums.forum-reply-posted-row',
                [
                    'title' => $thread['title'],
                    'content' => $post['content'],
                    'displayName' => $author->getDisplayName(),
                    'avatarUrl' => $author->getAvatar(),
                    'contentUrl' => url()->route('forums.post.jump-to', $post['id']),
                ]
            )->render();
        }

        foreach ($notificationsViews as $recipientEmail => $notificationViews) {

            if (count($notificationViews) > 1) {
                $subject = 'You Have ' . count($notificationViews) . ' New Notifications';
            } else {
                $subject = config('railnotifications.newThreadPostSubject');
            }

            $this->mailer->send(
                new AggregatedNotificationsEmail(
                    $recipientEmail, $notificationViews, $subject
                )
            );
        }
    }
}