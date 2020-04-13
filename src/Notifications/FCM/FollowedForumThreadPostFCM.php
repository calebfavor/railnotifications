<?php

namespace Railroad\Railnotifications\Notifications\FCM;

use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use Railroad\Railnotifications\Contracts\RailforumProviderInterface;
use Railroad\Railnotifications\Contracts\UserProviderInterface;

class FollowedForumThreadPostFCM
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var RailforumProviderInterface
     */
    private $railforumProvider;

    /**
     * FollowedForumThreadPostFCM constructor.
     *
     * @param UserProviderInterface $userProvider
     * @param RailforumProviderInterface $railforumProvider
     */
    public function __construct(UserProviderInterface $userProvider, RailforumProviderInterface $railforumProvider)
    {
        $this->userProvider = $userProvider;
        $this->railforumProvider = $railforumProvider;
    }

    /**
     * @param $notification
     * @return mixed
     */
    public function send($notification)
    {
        try {
            $post = $this->railforumProvider->getPostById($notification->getData()['postId']);

            $thread = $this->railforumProvider->getThreadById($post->thread_id);

            /**
             * @var $author User
             */
            $author = $this->userProvider->getRailnotificationsUserById($post['author_id']);

            $receivingUser = $notification->getRecipient();

            $firebaseTokens = $this->userProvider->getUserFirebaseTokens($receivingUser->getId());
            $tokens = [];
            foreach ($firebaseTokens as $firebaseToken) {
                $tokens[] = $firebaseToken->getToken();
            }

            $fcmTitle = $author->getDisplayName() . ' posted in a thread you follow.';
            $fcmMessage = $thread['title'];
            $fcmMessage .= '
' . mb_strimwidth(
                    htmlspecialchars(strip_tags($post['content'])),
                    0,
                    120,
                    "..."
                );

            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60 * 20);

            $notificationBuilder = new PayloadNotificationBuilder($fcmTitle);
            $notificationBuilder->setBody($fcmMessage)
                ->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData(
                [
                    'image' => $author->getAvatar(),
                ]
            );

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

            $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

            $this->userProvider->deleteUserFirebaseTokens($receivingUser->getId(), $downstreamResponse->tokensToDelete());

            foreach ($downstreamResponse->tokensToModify() as $oldToken => $newToken) {
                $this->userProvider->updateUserFirebaseToken($receivingUser->getId(), $oldToken, $newToken);
            }

            return $downstreamResponse;

        } catch (\Exception $messagingException) {

        }
    }
}