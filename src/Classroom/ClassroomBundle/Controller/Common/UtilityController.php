<?php
namespace Classroom\ClassroomBundle\Controller\Common;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\WebBundle\Controller\BaseController;

class UtilityController extends BaseController
{
    public function headteacherMatchAction(Request $request, $classroomId)
    {
        $likeString = $request->query->get('q');
        $users = $this->getUserService()->searchUsers(array(
            'nickname' => $likeString,
            'roles' => 'ROLE_TEACHER',
            'excludeIds' => $this->_getExcludeIds($classroomId),
            ), array('createdTime', 'DESC'), 0, 10
        );

        $newUsers = array();
        foreach ($users as $user) {
            $newUsers[] = array(
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar' => $this->getWebExtension()->getFilePath($user['smallAvatar'], 'avatar.png'),
            );
        }

        return $this->createJsonResponse($newUsers);
    }

    public function assistantsMatchAction(Request $request, $classroomId)
    {
        $likeString = $request->query->get('q');
        $users = $this->getUserService()->searchUsers(array(
            'nickname' => $likeString,
            'excludeIds' => $this->_getExcludeIds($classroomId),
            ), array('createdTime', 'DESC'), 0, 10
        );

        $newUsers = array();
        foreach ($users as $user) {
            $newUsers[] = array(
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar' => $this->getWebExtension()->getFilePath($user['smallAvatar'], 'avatar.png'),
            );
        }
        return $this->createJsonResponse($newUsers);
    }

    private function _getExcludeIds($classroomId)
    {
        $classroom = $this->getClassroomService()->getClassroom($classroomId);
        $assistants = $this->getClassroomService()->findAssistants($classroomId);
        $assistantIds = ArrayToolkit::column($assistants, 'userId');
        if(empty($assistantIds)){
            $assistantIds = array();
        }
        $excludeIds = $assistantIds;
        $excludeIds[] = $classroom['headTeacherId'];

        return $excludeIds;
    }

    private function getClassroomService()
    {
        return $this->getServiceKernel()->createService('Classroom:Classroom.ClassroomService');
    }

    private function getWebExtension()
    {
        return $this->container->get('topxia.twig.web_extension');
    }
}
