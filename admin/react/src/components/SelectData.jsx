import Table from './Table'
import ProgressBar from './ProgressBar'
import {
  deleteWpNodes,
  getCustomNodes,
  restoreWpNodes,
  saveWpNodes,
  updateCustomMapLastUpdated,
  updateCustomNodes,
  updateCustomNodesStatus,
  updateWpNodes
} from '../utils/api'
import { formDefaults } from '../data/formDefaults'
import { useState } from 'react'
import {
  removeSelectedProfiles,
  removeUnselectedProfiles
} from '../utils/filterProfile'
import PropTypes from 'prop-types'

export default function SelectData({
  profileList,
  setProfileList,
  isLoading,
  setIsLoading,
  isRetrieving,
  setIsMapSelected,
  setProgress,
  progress,
  setFormData,
  getMaps,
  setIsPopupOpen,
  setOriginalJson,
  setModifiedJson,
  currentTime,
  setCurrentTime,
  setDeletedProfiles,
  setUnauthorizedProfiles
}) {
  const [selectedIds, setSelectedIds] = useState([])
  const [selectedStatusOption, setSelectedStatusOption] = useState('publish')

  const toggleSelectAll = () => {
    profileList = profileList.filter(
      response => response.data.is_available && response.data.has_authority
    )
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

  const handleRetrieveProfilesSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      // get the selected profiles
      const selectedProfiles = removeUnselectedProfiles(
        profileList,
        selectedIds
      )
      const progressStep = 100 / selectedProfiles.length

      for (let i = 0; i < selectedProfiles.length; i++) {
        // update progress
        updateProgress(i, progressStep)

        const profile = selectedProfiles[i]

        // check the node is original unavailable or not
        const customNodeResponse = await getCustomNodes(
          profile.data.map_id,
          profile.index_data.profile_url
        )
        const customNodeResponseData = await customNodeResponse.json()
        if (!customNodeResponse.ok) {
          alert(
            `Get Custom Node Error: ${
              customNodeResponse.status
            } ${JSON.stringify(customNodeResponseData)}`
          )
        }

        // if data has update or is originally unavailable, we need to update the node first
        if (
          profile.data.extra_notes === 'see updates' ||
          !customNodeResponseData[0].is_available
        ) {
          const profileResponse = await updateCustomNodes(profile)

          if (!profileResponse.ok) {
            const profileResponseData = await profileResponse.json()
            alert(
              `Update Profile Error: ${profileResponse.status} ${JSON.stringify(
                profileResponseData
              )}`
            )
          }
        }

        const profileStatus = profile.data.status
        // if original status and selected status are both publish, we need to update the post
        if (
          selectedStatusOption === 'publish' &&
          (profileStatus === 'publish' ||
            profileStatus === 'trash' ||
            profileStatus === 'draft')
        ) {
          if (profileStatus === 'trash' || profileStatus === 'draft') {
            // restore the post
            const restoreNodeResponse = await restoreWpNodes(
              profile.data.post_id
            )

            if (!restoreNodeResponse.ok) {
              const restoreNodeResponseData = await restoreNodeResponse.json()
              alert(
                `Restore Profile Error: ${
                  restoreNodeResponse.status
                } ${JSON.stringify(restoreNodeResponseData)}`
              )
            }
          }

          const profileResponse = await updateWpNodes(profile)

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
          (profileStatus === 'new' ||
            profileStatus === 'dismiss' ||
            profileStatus === 'ignore' ||
            profileStatus === 'deleted')
        ) {
          const profileResponse = await saveWpNodes(profile)
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
          selectedStatusOption === 'dismiss' ||
          selectedStatusOption === 'ignore'
        ) {
          if (
            profileStatus === 'publish' ||
            profileStatus === 'trash' ||
            profileStatus === 'draft'
          ) {
            const deleteNodeResponse = await deleteWpNodes(profile.data.post_id)

            if (!deleteNodeResponse.ok) {
              const deleteNodeResponseData = await deleteNodeResponse.json()
              alert(
                `Delete Profile Error: ${
                  deleteNodeResponse.status
                } ${JSON.stringify(deleteNodeResponseData)}`
              )
            }
          }

          // update the status of the node
          profile.data.status = selectedStatusOption
          const profileResponse = await updateCustomNodesStatus(profile)
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

      await updateProfileAndRefresh(profileList, selectedIds)
    } catch (error) {
      setIsMapSelected(false)
      alert(
        `Handle Profiles Submit error: ${error}, please delete the map and retry again.`
      )
    } finally {
      setIsMapSelected(false)
      resetStates()
    }
  }

  const handleProfilesSubmit = async event => {
    event.preventDefault()
    setIsLoading(true)

    try {
      // get the selected profiles
      const selectedProfiles = removeUnselectedProfiles(
        profileList,
        selectedIds
      )
      const progressStep = 100 / selectedProfiles.length

      for (let i = 0; i < selectedProfiles.length; i++) {
        // update progress
        updateProgress(i, progressStep)

        const profile = selectedProfiles[i]

        // if the profile wants to publish, it will create post in WordPress
        // otherwise, dismiss, ignore status will update the status of nodes table
        let profileResponse
        if (
          selectedStatusOption === 'publish' &&
          profile.data.status !== 'publish'
        ) {
          profileResponse = await saveWpNodes(profile)
        } else {
          profile.data.status = selectedStatusOption
          profileResponse = await updateCustomNodesStatus(profile)
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

      await updateProfileAndRefresh(profileList, selectedIds)
    } catch (error) {
      setIsMapSelected(false)
      alert(
        `Handle Profiles Submit error: ${error}, please delete the map and retry again.`
      )
    } finally {
      setIsMapSelected(false)
      resetStates()
    }
  }

  const updateProfileAndRefresh = async (profileList, selectedIds) => {
    // remove selected profiles
    const newProfileList = removeSelectedProfiles(profileList, selectedIds)

    // get tag_slug
    const mapId = profileList[0].data.map_id

    // if the extra_notes of all profiles are unavailable, it means all nodes are handled, we can refresh the page
    if (
      newProfileList.length === 0 ||
      newProfileList.every(profile => !profile.data.is_available || !profile.data.has_authority)
    ) {
      if (currentTime !== '') {
        const response = await updateCustomMapLastUpdated(mapId, currentTime)
        if (!response.ok) {
          const responseData = await response.json()
          alert(
            `Update Last Updated Error: ${response.status} ${JSON.stringify(
              responseData
            )}`
          )
        }

        setCurrentTime('')
      }

      setFormData(formDefaults)
      setProfileList([])
      await getMaps()
    } else {
      setProfileList(newProfileList)
    }
  }

  const updateProgress = (i, progressStep) => {
    setProgress((i + 1) * progressStep > 100 ? 100 : (i + 1) * progressStep)
  }

  const resetStates = () => {
    setSelectedIds([])
    setProgress(0)
    setIsLoading(false)
    setDeletedProfiles([])
    setUnauthorizedProfiles([])
  }

  const handleDropdownChange = function () {
    const selected = document.getElementById('status-option').value
    setSelectedStatusOption(selected)
  }

  const handleCancel = () => {
    setIsMapSelected(false)
    setDeletedProfiles([])
    setUnauthorizedProfiles([])
    window.scrollTo(0, 0)
  }

  return (
    <div>
      {isLoading && <ProgressBar progress={progress} />}
      <h2 className="mb-8 text-2xl">Select Nodes</h2>
      <p className="mb-8 text-base">
        Manage the nodes to display in your map or directory. You can learn more{' '}
        <a
          href="https://docs.murmurations.network/developers/wp-aggregator.html#managing-nodes"
          target="_blank"
          className="text-blue-500 underline"
          rel="noreferrer"
        >
          in the docs
        </a>
        .
      </p>
      <Table
        tableList={profileList}
        selectedIds={selectedIds}
        onSelectAll={toggleSelectAll}
        onSelect={toggleSelect}
        setIsPopupOpen={setIsPopupOpen}
        setOriginalJson={setOriginalJson}
        setModifiedJson={setModifiedJson}
      />
      <form
        onSubmit={
          isRetrieving ? handleRetrieveProfilesSubmit : handleProfilesSubmit
        }
        className="py-6"
      >
        <div className="mt-6">
          <div className="inline-block align-middle">
            <label className="text-lg">
              Select Action:
              <select
                id="status-option"
                value={selectedStatusOption}
                onChange={() => handleDropdownChange()}
                className="mx-4"
              >
                <option value="publish">Publish</option>
                <option value="dismiss">Dismiss</option>
                <option value="ignore">Ignore</option>
              </select>
            </label>
            <button
              type="submit"
              className={`mx-4 rounded-full bg-orange-500 px-4 py-2 text-lg font-bold text-white hover:scale-110 hover:bg-orange-400 active:scale-90 disabled:opacity-75 ${
                isLoading ? 'cursor-not-allowed opacity-50' : ''
              }`}
            >
              {isLoading ? 'Submitting...' : 'Submit'}
            </button>
            <button
              onClick={handleCancel}
              className="mx-4 rounded-full bg-gray-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-gray-400 active:scale-90 disabled:opacity-75"
            >
              Cancel
            </button>
          </div>
        </div>
      </form>
      <div className="mt-2 text-sm">
        Publish = display node on map <br /> Dismiss = hide node until it has
        updates <br /> Ignore = always hide node
      </div>
    </div>
  )
}

SelectData.propTypes = {
  profileList: PropTypes.array.isRequired,
  setProfileList: PropTypes.func.isRequired,
  isLoading: PropTypes.bool.isRequired,
  setIsLoading: PropTypes.func.isRequired,
  isRetrieving: PropTypes.bool.isRequired,
  setIsMapSelected: PropTypes.func.isRequired,
  setProgress: PropTypes.func.isRequired,
  progress: PropTypes.number.isRequired,
  setFormData: PropTypes.func.isRequired,
  getMaps: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  setModifiedJson: PropTypes.func.isRequired,
  currentTime: PropTypes.string.isRequired,
  setCurrentTime: PropTypes.func.isRequired,
  setDeletedProfiles: PropTypes.func.isRequired,
  setUnauthorizedProfiles: PropTypes.func.isRequired
}
