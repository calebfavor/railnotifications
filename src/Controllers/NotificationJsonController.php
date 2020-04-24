<?php

namespace Railroad\Railnotifications\Controllers;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Railnotifications\Exceptions\NotFoundException;
use Railroad\Railnotifications\Requests\NotificationRequest;
use Railroad\Railnotifications\Services\NotificationService;
use Railroad\Railnotifications\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Throwable;

/**
 * Class NotificationJsonController
 *
 * @group Notifications API
 *
 * @package Railroad\Railnotifications\Controllers
 */
class NotificationJsonController extends Controller
{
    /**
     * @var NotificationService
     */
    private $notificationService;

    /**
     * NotificationJsonController constructor.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(
        NotificationService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    /**
     * @param Request $request
     * @return Fractal
     */
    public function index(Request $request)
    {
        $userId = $request->get('user_id', auth()->id());

        $notifications = $this->notificationService->getMany([$userId]);

        return ResponseService::notification($notifications);
    }

    /**
     * @param Request $request
     * @return Fractal
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store(NotificationRequest $request)
    {
        $notification = $this->notificationService->create(
            $request->get('type'),
            $request->get('data'),
            $request->get('recipient_id')
        );

        return ResponseService::notification($notification);
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     *
     * @param integer $id - notification id
     */
    public function delete($id)
    {
        $this->notificationService->destroy($id);

        return ResponseService::empty(204);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return Fractal
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     *
     * @param integer $id - notification id
     */
    public function markAsRead(int $id, Request $request)
    {
        $notification = $this->notificationService->markRead(
            $id,
            $request->get('read_on_date_time')
        );

        throw_if(
            is_null($notification),
            new NotFoundException('Notification not found with id: ' . $id)
        );

        return ResponseService::notification($notification);
    }

    /**
     * @param Request $request
     * @return Fractal
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function markAllAsRead( Request $request)
    {
        $recipientId = $request->get('user_id', auth()->user());
        $notifications = $this->notificationService->markAllRead(
            $recipientId,
            $request->get('read_on_date_time')
        );

        return ResponseService::notification($notifications);
    }

    /**
     * @param Request $request
     * @return Fractal
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function syncNotification(NotificationRequest $request)
    {
        $notification = $this->notificationService->createOrUpdateWhereMatchingData(
            $request->get('type'),
            $request->get('data'),
            $request->get('recipient_id')
        );

        return ResponseService::notification($notification);
    }

    /**
     * @param $id
     * @return Fractal
     * @throws Throwable
     *
     * @param integer $id - notification id
     */
    public function showNotification($id)
    {
        $notification = $this->notificationService->get(
            $id
        );

        throw_if(
            is_null($notification),
            new NotFoundException('Notification not found with id: ' . $id)
        );

        return ResponseService::notification($notification);
    }

    /**
     * @param int $id
     * @return Fractal
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     *
     * @param integer $id - notification id
     */
    public function markAsUnRead(int $id)
    {
        $notification = $this->notificationService->markUnRead($id);

        throw_if(
            is_null($notification),
            new NotFoundException('Notification not found with id: ' . $id)
        );

        return ResponseService::notification($notification);
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countReadNotifications(Request $request)
    {
        $count = $this->notificationService->getReadCount($request->get('user_id', auth()->id()));

        return ResponseService::empty(201)
            ->setData(['data' => $count]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countUnReadNotifications(Request $request)
    {
        $count = $this->notificationService->getUnreadCount($request->get('user_id', auth()->id()));

        return ResponseService::empty(201)
            ->setData(['data' => $count]);
    }
}
