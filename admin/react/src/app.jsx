import { useEffect, useState } from 'react'
import MapList from './components/MapList'
import { formDefaults } from './data/formDefaults'
import { getCustomMaps } from './utils/api'
import SelectData from './components/SelectData'
import EditData from './components/EditData'
import CreateData from './components/CreateData'
import PopupBox from './components/PopupBox'

export default function App() {
  // button states
  const [isLoading, setIsLoading] = useState(false)
  const [isRetrieving, setIsRetrieving] = useState(false)
  const [isEdit, setIsEdit] = useState(false)

  // MapList states
  const [maps, setMaps] = useState([])
  const [currentTime, setCurrentTime] = useState(null)

  // DataSource states
  const [formData, setFormData] = useState(formDefaults)
  const [tagSlug, setTagSlug] = useState(null)

  // SelectData states
  const [profileList, setProfileList] = useState([])

  // ProgressBar states
  const [progress, setProgress] = useState(0)

  // PopupBox states
  const [isPopupOpen, setIsPopupOpen] = useState(false)
  const [originalJson, setOriginalJson] = useState({})
  const [modifiedJson, setModifiedJson] = useState({})

  useEffect(() => {
    getMaps().then(() => {
      console.log('maps fetched')
    })
  }, [])

  const getMaps = async () => {
    try {
      const response = await getCustomMaps()
      if (response.status === 404) {
        setMaps([])
        return
      }

      const responseData = await response.json()
      if (!response.ok) {
        alert(
          `Error fetching map with response: ${
            response.status
          } ${JSON.stringify(responseData)}`
        )
        return
      }

      setMaps(responseData)
    } catch (error) {
      alert(
        `Error getting maps, please contact the administrator, error: ${error}`
      )
    }
  }

  const handleInputChange = event => {
    const { name, value, type, checked } = event.target
    const newValue = type === 'checkbox' ? checked : value

    setFormData(prevData => {
      const newData = Object.assign({}, prevData)
      newData[name] = newValue
      return newData
    })
  }

  return (
    <div>
      <h1 className="text-3xl">Murmurations Aggregator</h1>
      <div className="flex">
        <div className="w-1/2 mt-4 p-4">
          {profileList.length === 0 ? (
            isEdit ? (
              <EditData
                formData={formData}
                handleInputChange={handleInputChange}
                setIsLoading={setIsLoading}
                setIsEdit={setIsEdit}
                setFormData={setFormData}
                setTagSlug={setTagSlug}
                tagSlug={tagSlug}
                getMaps={getMaps}
                isLoading={isLoading}
              />
            ) : (
              <CreateData
                formData={formData}
                handleInputChange={handleInputChange}
                setIsLoading={setIsLoading}
                setIsRetrieving={setIsRetrieving}
                setProfileList={setProfileList}
                progress={progress}
                setProgress={setProgress}
                isLoading={isLoading}
                setCurrentTime={setCurrentTime}
              />
            )
          ) : (
            <SelectData
              profileList={profileList}
              setProfileList={setProfileList}
              isLoading={isLoading}
              setIsLoading={setIsLoading}
              isRetrieving={isRetrieving}
              setProgress={setProgress}
              progress={progress}
              setFormData={setFormData}
              getMaps={getMaps}
              setIsPopupOpen={setIsPopupOpen}
              setOriginalJson={setOriginalJson}
              setModifiedJson={setModifiedJson}
              currentTime={currentTime}
              setCurrentTime={setCurrentTime}
            />
          )}
        </div>
        <div className="w-1/2 mt-4 p-4">
          <MapList
            maps={maps}
            getMaps={getMaps}
            setIsEdit={setIsEdit}
            setFormData={setFormData}
            setProfileList={setProfileList}
            setIsRetrieving={setIsRetrieving}
            setTagSlug={setTagSlug}
            setIsLoading={setIsLoading}
            isLoading={isLoading}
            setCurrentTime={setCurrentTime}
            setProgress={setProgress}
          />
        </div>
      </div>
      <PopupBox
        isPopupOpen={isPopupOpen}
        setIsPopupOpen={setIsPopupOpen}
        originalJson={originalJson}
        setOriginalJson={setOriginalJson}
        modifiedJson={modifiedJson}
        setModifiedJson={setModifiedJson}
      />
    </div>
  )
}
