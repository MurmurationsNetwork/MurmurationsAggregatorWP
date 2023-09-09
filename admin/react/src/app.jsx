import { useEffect, useState } from 'react'
import Table from './components/Table'
import MapSettings from './components/MapSettings'
import { createId } from '@paralleldrive/cuid2'
import MapList from './components/MapList'
import { formDefaults } from './data/formDefaults'
import {
  deleteWpNodes,
  getWpMaps,
  saveCustomNodes,
  saveWpMap,
  saveWpNodes,
  updateCustomNodes,
  updateCustomNodesStatus,
  updateWpMap,
  updateWpNodes
} from './utils/api'
import DataSource from './components/DataSource'
import ProgressBar from './components/ProgressBar'

export default function App() {
  // eslint-disable-next-line no-undef
  const wordpressUrl = murmurations_aggregator.wordpress_url
  const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1`

  const [formData, setFormData] = useState(formDefaults)
  const [maps, setMaps] = useState([])
  const [selectedCountry, setSelectedCountry] = useState([])
  const [profileList, setProfileList] = useState([])
  const [selectedIds, setSelectedIds] = useState([])
  const [isLoading, setIsLoading] = useState(false)
  const [progress, setProgress] = useState(0)
  const [isEdit, setIsEdit] = useState(false)
  const [selectedStatusOption, setSelectedStatusOption] = useState('publish')
  const [isRetrieving, setIsRetrieving] = useState(false)
  const [tagSlug, setTagSlug] = useState(null)

  useEffect(() => {
    getMaps().then(() => {
      console.log('maps fetched')
    })
  }, [])

  const getMaps = async () => {
    try {
      const response = await getWpMaps(apiUrl)
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

  // Table
  const toggleSelectAll = () => {
    if (selectedIds.length === profileList.length) {
      setSelectedIds([])
    } else {
      setSelectedIds(profileList.map(response => response.id))
    }
  }

  const toggleSelect = id => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter(selectedId => selectedId !== id))
    } else {
      setSelectedIds([...selectedIds, id])
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

  const handleSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)
    setIsRetrieving(false)

    const queryParams = []
    for (const key in formData) {
      if (formData[key] !== '' && key !== 'data_url' && key !== 'map_name') {
        queryParams.push(
          `${encodeURIComponent(key)}=${encodeURIComponent(formData[key])}`
        )
      }
    }

    // handle country array
    if (selectedCountry.length > 0) {
      queryParams.push(
        `${encodeURIComponent('country')}=${encodeURIComponent(
          selectedCountry.join(',')
        )}`
      )
    }

    const queryString = queryParams.join('&')
    const pageQueries = 'page=1&page_size=500'

    const urlWithParams =
      formData.data_url +
      '?' +
      (queryString ? `${queryString}&` : '') +
      pageQueries

    try {
      const response = await fetch(urlWithParams)
      if (response.ok) {
        const responseData = await response.json()
        if (responseData.data && responseData.data.length === 0) {
          alert('Error: No data found. Please try again.')
          return
        }
        if (
          responseData.meta &&
          responseData.meta.total_pages &&
          responseData.meta.total_pages > 2
        ) {
          alert('Error: Too many pages of data. Please narrow your search.')
          return
        }

        // we have a valid response, we can save the map to the server
        const tagSlug = 'murm_' + createId()
        const mapResponse = await saveWpMap(
          apiUrl,
          formData.map_name,
          tagSlug,
          formData.data_url,
          urlWithParams.replace(formData.data_url, '')
        )
        const mapResponseData = await mapResponse.json()
        if (!mapResponse.ok) {
          alert(
            `Map Error: ${mapResponse.status} ${JSON.stringify(
              mapResponseData
            )}`
          )
          return
        }

        // set the profileList and save the data to wpdb
        const profiles = responseData.data
        const dataWithIds = []
        const progressStep = 100 / profiles.length
        for (let i = 0; i < profiles.length; i++) {
          // update progress
          if ((i + 1) * progressStep > 100) {
            setProgress(100)
          } else {
            setProgress((i + 1) * progressStep)
          }

          const profile = profiles[i]
          let profile_data = ''
          if (profile.profile_url) {
            const response = await fetch(profile.profile_url)
            if (response.ok) {
              profile_data = await response.json()
            }
          }
          profile.id = i + 1
          profile.profile_data = profile_data
          profile.status = 'new'
          profile.map_id = mapResponseData.map_id
          profile.tag_slug = tagSlug

          // save data to wpdb
          // todo: status needs to update according to the settings
          const profileResponse = await saveCustomNodes(
            apiUrl,
            profile.profile_url,
            profile.profile_data,
            profile.map_id,
            profile.status
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Unable to save profiles to wpdb, errors: ${
                profileResponse.status
              } ${JSON.stringify(
                profileResponseData
              )}. Please delete the map and try again.`
            )
            return
          }

          // set extra notes
          profile.extra_notes = profile_data === '' ? 'unavailable' : ''

          dataWithIds.push(profile)
        }
        setProfileList(dataWithIds)
      } else {
        alert(`Error: ${response.status} ${response}`)
      }
    } catch (error) {
      alert(
        `Handle Submit error: ${JSON.stringify(
          error
        )}, please delete the map and retry again.`
      )
    } finally {
      setIsLoading(false)
      setProgress(0)
    }
  }

  const handleProfilesSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      // get the selected profiles
      const selectedProfiles = profileList.filter(profile =>
        selectedIds.includes(profile.id)
      )
      const progressStep = 100 / selectedProfiles.length

      for (let i = 0; i < selectedProfiles.length; i++) {
        // update progress
        if ((i + 1) * progressStep > 100) {
          setProgress(100)
        } else {
          setProgress((i + 1) * progressStep)
        }

        const profile = selectedProfiles[i]

        // if the profile wants to publish, it will create post in WordPress
        // otherwise, dismiss, ignore status will update the status of nodes table
        let profileResponse
        if (selectedStatusOption === 'publish') {
          profileResponse = await saveWpNodes(apiUrl, profile.tag_slug, profile)
        } else {
          profileResponse = await updateCustomNodesStatus(
            apiUrl,
            profile.map_id,
            profile.profile_url,
            selectedStatusOption
          )
        }

        // todo: needs to summarize errors and display them in once
        if (!profileResponse.ok) {
          const profileResponseData = await profileResponse.json()
          alert(
            `Profile Error: ${profileResponse.status} ${JSON.stringify(
              profileResponseData
            )}`
          )
        }
      }

      // remove selected profiles
      const newProfileList = profileList.filter(
        profile => !selectedIds.includes(profile.id)
      )

      // if the extra_notes of all profiles are unavailable, it means all nodes are handled, we can refresh the page
      if (
        newProfileList.length === 0 ||
        newProfileList.every(profile => profile.extra_notes === 'unavailable')
      ) {
        setFormData(formDefaults)
        setSelectedCountry([])
        setProfileList([])
        await getMaps()
      } else {
        setProfileList(newProfileList)
      }
    } catch (error) {
      alert(
        `Handle Profiles Submit error: ${JSON.stringify(
          error
        )}, please delete the map and retry again.`
      )
    } finally {
      // set everything back to default
      setSelectedIds([])
      setProgress(0)
      setIsLoading(false)
    }
  }

  const handleRetrieveProfilesSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      // get the selected profiles
      const selectedProfiles = profileList.filter(profile =>
        selectedIds.includes(profile.id)
      )
      const progressStep = 100 / selectedProfiles.length

      for (let i = 0; i < selectedProfiles.length; i++) {
        // update progress
        if ((i + 1) * progressStep > 100) {
          setProgress(100)
        } else {
          setProgress((i + 1) * progressStep)
        }

        const profile = selectedProfiles[i]

        // need to update the node first
        if (profile.extra_notes === 'see updates') {
          const profileResponse = await updateCustomNodes(
            apiUrl,
            profile.map_id,
            profile.profile_url,
            profile.profile_data
          )

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Update Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        const profileStatus = profile.status
        // if original status and selected status are both publish, we need to update the post
        if (selectedStatusOption === 'publish' && profileStatus === 'publish') {
          const profileResponse = await updateWpNodes(apiUrl, profile)

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Update Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        if (
          selectedStatusOption === 'publish' &&
          (profileStatus === 'new' || profileStatus === 'dismiss')
        ) {
          const profileResponse = await saveWpNodes(
            apiUrl,
            profile.tag_slug,
            profile
          )
          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Create Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        if (
          (selectedStatusOption === 'dismiss' ||
            selectedStatusOption === 'ignore') &&
          profileStatus === 'publish'
        ) {
          const response = await deleteWpNodes(apiUrl, profile)

          if (!response.ok) {
            const responseData = await response.json()
            alert(
              `Delete Profile Error: ${response.status} ${JSON.stringify(
                responseData
              )}`
            )
          }

          // update the status of the node
          const profileResponse = await updateCustomNodesStatus(
            apiUrl,
            profile.map_id,
            profile.profile_url,
            selectedStatusOption
          )
          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Node Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        if (selectedStatusOption === 'ignore' && profileStatus === 'dismiss') {
          // update the status of the node
          const profileResponse = await updateCustomNodesStatus(
            apiUrl,
            profile.map_id,
            profile.profile_url,
            selectedStatusOption
          )
          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Node Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }
      }

      // remove selected profiles
      const newProfileList = profileList.filter(
        profile => !selectedIds.includes(profile.id)
      )

      // if the extra_notes of all profiles are unavailable, it means all nodes are handled, we can refresh the page
      if (
        newProfileList.length === 0 ||
        newProfileList.every(profile => profile.extra_notes === 'unavailable')
      ) {
        setFormData(formDefaults)
        setSelectedCountry([])
        setProfileList([])
        await getMaps()
      } else {
        setProfileList(newProfileList)
      }
    } catch (error) {
      alert(
        `Handle Profiles Submit error: ${JSON.stringify(
          error
        )}, please delete the map and retry again.`
      )
    } finally {
      // set everything back to default
      setSelectedIds([])
      setProgress(0)
      setIsLoading(false)
    }
  }

  const handleEditSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      const response = await updateWpMap(
        apiUrl,
        tagSlug,
        formData.map_name,
        formData.map_center_lat,
        formData.map_center_lon,
        formData.map_scale
      )

      if (!response.ok) {
        const responseData = await response.json()
        alert(`Map Error: ${response.status} ${JSON.stringify(responseData)}`)
      }
    } catch (error) {
      alert(`Edit map error: ${JSON.stringify(error)}`)
    } finally {
      setIsEdit(false)
      setIsLoading(false)
      setFormData(formDefaults)
      setTagSlug(null)
      await getMaps()
    }
  }

  const handleDropdownChange = function () {
    const selected = document.getElementById('status-option').value
    setSelectedStatusOption(selected)
  }

  return (
    <div>
      <h1 className="text-3xl">Murmurations Aggregator</h1>

      <div className="flex">
        <div className="w-1/2 mt-4 p-4">
          {profileList.length === 0 ? (
            isEdit ? (
              <div>
                <form onSubmit={handleEditSubmit} className="p-6">
                  <MapSettings
                    formData={formData}
                    handleInputChange={handleInputChange}
                  />
                  <div className="mt-6">
                    <button
                      type="submit"
                      className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
                        isLoading ? 'opacity-50 cursor-not-allowed' : ''
                      }`}
                    >
                      {isLoading ? 'Submitting...' : 'Submit'}
                    </button>
                  </div>
                </form>
              </div>
            ) : (
              <div>
                <h2 className="text-xl">Create Data Source</h2>
                <form onSubmit={handleSubmit} className="p-6">
                  <MapSettings
                    formData={formData}
                    handleInputChange={handleInputChange}
                  />
                  <DataSource
                    formData={formData}
                    handleInputChange={handleInputChange}
                    selectedCountry={selectedCountry}
                    setSelectedCountry={setSelectedCountry}
                  />
                  {isLoading && <ProgressBar progress={progress} />}
                  <div className="mt-6">
                    <button
                      type="submit"
                      className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
                        isLoading ? 'opacity-50 cursor-not-allowed' : ''
                      }`}
                    >
                      {isLoading ? 'Submitting...' : 'Submit'}
                    </button>
                  </div>
                </form>
              </div>
            )
          ) : (
            <div>
              <h2 className="text-xl mt-4">Data Select</h2>
              <Table
                tableList={profileList}
                selectedIds={selectedIds}
                onSelectAll={toggleSelectAll}
                onSelect={toggleSelect}
              />
              <form
                onSubmit={
                  isRetrieving
                    ? handleRetrieveProfilesSubmit
                    : handleProfilesSubmit
                }
                className="p-6"
              >
                <div className="mt-6">
                  {isLoading && <ProgressBar progress={progress} />}
                  <div className="flex items-start">
                    <label className="mr-2">Select Action:</label>
                    <select
                      id="status-option"
                      value={selectedStatusOption}
                      onChange={() => handleDropdownChange()}
                      className="mr-2"
                    >
                      <option value="publish">Publish</option>
                      <option value="dismiss">Dismiss</option>
                      <option value="ignore">Ignore</option>
                    </select>
                    <button
                      type="submit"
                      className={`rounded-full bg-orange-500 px-4 py-2 font-bold text-white text-lg active:scale-90 hover:scale-110 hover:bg-orange-400 disabled:opacity-75 ${
                        isLoading ? 'opacity-50 cursor-not-allowed' : ''
                      }`}
                    >
                      {isLoading ? 'Submitting...' : 'Submit'}
                    </button>
                  </div>
                </div>
              </form>
            </div>
          )}
        </div>
        <div className="w-1/2 mt-4 p-4">
          <MapList
            apiUrl={apiUrl}
            maps={maps}
            getMaps={getMaps}
            setIsEdit={setIsEdit}
            setFormData={setFormData}
            setProfileList={setProfileList}
            setIsRetrieving={setIsRetrieving}
            setTagSlug={setTagSlug}
            setIsLoading={setIsLoading}
            isLoading={isLoading}
          />
        </div>
      </div>
    </div>
  )
}
